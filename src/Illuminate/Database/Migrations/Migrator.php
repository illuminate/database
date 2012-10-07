<?php namespace Illuminate\Database\Migrations;

use Closure;
use Illuminate\Filesystem;
use Illuminate\Database\Connection;
use Symfony\Component\Console\Output\OutputInterface;

class Migrator {

	/**
	 * The migration repository implementation.
	 *
	 * @var Illuminate\Database\Migrations\MigrationRepositoryInterface
	 */
	protected $repository;

	/**
	 * The filesystem instance.
	 *
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * The connection pool array.
	 *
	 * @var array
	 */
	protected $connectionPool = array();

	/**
	 * The name of the default connection.
	 *
	 * @var string
	 */
	protected $defaultConnection;

	/**
	 * Create a new migrator instance.
	 *
	 * @param  Illuminate\Database\Migrations\MigrationRepositoryInterface  $repository
	 * @param  Illuminate\Filesystem  $files
	 */
	public function __construct(MigrationRepositoryInterface $repository,
                                Filesystem $files)
	{
		$this->files = $files;
		$this->repository = $repository;
	}

	/**
	 * Run the outstanding migrations at a given path.
	 *
	 * @param  Symfony\Component\Console\Output\OutputInterface  $output
	 * @param  string  $package
	 * @param  string  $path
	 * @param  bool    $pretend
	 * @return void
	 */
	public function runMigrations(OuputInterface $output, $package, $path, $pretend = false)
	{
		$output->writeln('<info>Running migration path:</info> '.$path);

		// Once we grab all of the migration files for the path, we will compare them
		// against the migrations that have already been run for this package then
		// run all of the oustanding migrations against the database connection.
		$files = $this->getMigrationFiles($path);

		$ran = $this->repository->getRan($package);

		$migrations = array_diff($files, $ran);

		$this->runMigrationList($output, $migrations, $package, $pretend);
	}

	/**
	 * Run an array of migrations.
	 *
	 * @param  Symfony\Component\Console\Output\OutputInterface  $output
	 * @param  array   $migrations
	 * @param  string  $package
	 * @param  bool    $pretend
	 * @return void
	 */
	public function runMigrationList(OutputInterface $output, $migrations, $package, $pretend = false)
	{
		// First we will just make sure that there are any migrations to run. If there
		// aren't, we will just make a note of it to the developer so they're aware
		// that all of the migrations have been run against this database system.
		if (count($migrations) == 0)
		{
			$output->writeln('<info>No outstanding migrations.</info>');

			return;
		}

		$batch = $this->repository->getNextBatchNumber();

		// Once we have the array of migrations, we will spin through them and run the
		// migrations "up" so the changes are made to the databases. We'll then log
		// that the migration was run so we don't repeat it next time we execute.
		foreach ($migrations as $file)
		{
			$this->runUp($output, $package, $file, $pretend);
		}
	}

	/**
	 * Run "up" a migration instance.
	 *
	 * @param  Symfony\Component\Console\Output\OutputInterface  $output
	 * @param  string  $package
	 * @param  string  $file
	 * @param  bool    $pretend
	 * @return void
	 */
	protected function runUp($output, $package, $file, $pretend)
	{
		// First we will resolve a "real" instance of the migration class from this
		// migration file name. Once we have the instances we can run the actual
		// command such as "up" or "down", or we can just simulate the action.
		$migration = $this->resolve($file);

		if ($pretend)
		{
			return $this->pretendToRun($output, $migration, 'up');
		}

		$migration->up();

		// Once we have run a migrations class, we will log that it was run in this
		// repository so that we don't try to run it next time we do a migration
		// in the application. A migration repository keeps the migrate order.
		$this->repository->log($package, $file, $batch);

		$output->writeln("<info>Migrated:</info> $file");
	}

	/**
	 * Rollback the last migration operation.
	 *
	 * @param  Symfony\Component\Console\Output\OutputInterface  $output
	 * @param  bool  $pretend
	 * @return int
	 */
	public function rollbackMigrations(OutputInterface $output, $pretend = false)
	{
		// We want to pull in the last batch of migrations that ran on the previous
		// migration operation. We'll then reverse those migrations and run each
		// of them "down" to reverse the last migration "operation" which ran.
		$migrations = $this->repository->getLast();

		if (count($migrations) == 0)
		{
			$output->writeln('<info>Nothing to rollback.</info>');

			return count($migrations);
		}

		// We need to reverse these migrations so that they are "downed" in reverse
		// to what they run on "up". It lets us backtrack through the migrations
		// and properly reverse the entire database schema operation that ran.
		foreach (array_reverse($migrations) as $migration)
		{
			$this->runDown($output, $migration, $pretend);
		}

		return count($migrations);
	}

	/**
	 * Run "down" a migration instance.
	 *
	 * @param  Symfony\Component\Console\Output\OutputInterface  $output
	 * @param  StdClass  $migration
	 * @param  bool  $pretend
	 * @return void
	 */
	protected function runDown($output, $migration, $pretend)
	{
		$file = $migration->migration;

		// First we will get the file name of the migration so we can resolve out an
		// instance of the migration. Once we get an instance we can either run a
		// pretend execution of the migration or we can run the real migration.
		$instance = $this->resolve($file);

		if ($pretend)
		{
			return $this->pretendToRun($output, $instance, 'down');
		}

		$instance->down();

		// Once we have successfully run the migration "down" we will remove it from
		// the migration repository so it will be considered to have not been run
		// by the application then will be able to fire by any later operation.
		$this->repository->delete($migration);

		$output->writeln("<info>Rolled back:</info> $file");
	}

	/**
	 * Get all of the queries that would be run for a migration.
	 *
	 * @param  Symfony\Component\Console\Output\OutputInterface  $output
	 * @param  object  $migration
	 * @param  string  $method
	 * @return array
	 */
	protected function getQueries($output, $migration, $method)
	{
		// If the migration is marked as not pretendable, we will skip it and make a
		// note of it in the output. Some migration may be marked like this if it
		// they do other functions such as interact with things besides the DB.
		if ( ! $migration->pretend)
		{
			$class = get_class($migration);

			$output->writeln("<error>Can't simulate [$class].</error>");
		}

		$connection = $migration->connection;

		// Now that we have the connections we can resolve it and pretend to run the
		// queries against the database returning the array of raw SQL statements
		// that would get fired against the database system for this migration.
		$db = $this->resolveConnection($connection);

		return $db->pretend(function() use ($migration, $method)
		{
			$migration->$method();
		});
	}

	/**
	 * Get all of the migration files in a given path.
	 *
	 * @param  string  $path
	 * @return array
	 */
	public function getMigrationFiles($path)
	{
		$files = $this->files->glob($path.'/*_*.php');

		// Once we have the array of files in the directory we will just remove the
		// extension and take the basename of the file which is all we need when
		// finding the migrations that haven't been run against the databases.
		if ($files === false) return array();

		$files = array_map(function($file)
		{
			return str_replace('.php', '', basename($file));

		}, $files);

		// Once we have all of the formatted file names we will sort them and since
		// they all start with a timestamp this should give us the migrations in
		// the order they were actually created by the application developers.
		sort($files);

		return $files;
	}

	/**
	 * Pretend to run the migrations.
	 *
	 * @param  Symfony\Component\Console\Output\OutputInterface  $output
	 * @param  object  $migration
	 * @return void
	 */
	protected function pretendToRun($output, $migration, $method)
	{
		foreach ($this->getQueries($output, $migration, $method) as $query)
		{
			$output->writeln("<info>$query</info>");
		}
	}

	/**
	 * Resolve a migration instance from a file.
	 *
	 * @param  string  $file
	 * @return object
	 */
	protected function resolve($file)
	{
		$class = camel_case(array_slice(explode('_', $file), 1));

		return new $class;
	}

	/**
	 * Add a connection to the connection pool.
	 *
	 * @param  string  $name
	 * @param  Illuminate\Database\Connection|Closure  $connection
	 * @return void
	 */
	public function addConnection($name, $connection)
	{
		if (is_null($this->defaultConnection)) $this->defaultConnection = $name;

		$this->connectionPool[$name] = $connection;
	}

	/**
	 * Resolve a connection by name.
	 *
	 * @param  string  $name
	 * @return Illuminate\Database\Connection
	 */
	public function resolveConnection($name = null)
	{
		$name = $name ?: $this->defaultConnection;

		// We allow connections to be added to the pool as Closures so we can lazily
		// resolve them so we don't have to connect when we do not really need to
		// make the connection. So, if we have a Closure we'll execute it here.
		if (isset($this->connectionPool[$name]))
		{
			$value = $this->connectionPool[$name];

			return $value instanceof Closure ? call_user_func($value) : $value;
		}

		throw new \InvalidArgumentException("Undefined connection [$name]");
	}

}