<?php namespace Illuminate\Database\Console\Migrations;

use Closure;
use Illuminate\Filesystem;
use Illuminate\Database\Connection;
use Symfony\Component\Console\Output\OutputInterface;

class Migrator {

	/**
	 * The migration repository implementation.
	 *
	 * @var Illuminate\Database\Console\Migrations\MigrationRepositoryInterface
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
	 * @param  Illuminate\Database\Console\Migrations\MigrationRepositoryInterface  $repository
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
		$this->output->writeln('<info>Running migration path: '.$path.'</info>');

		// Once we grab all of the migration files for the path, we will compare them
		// against the migrations that have already been run for this package then
		// run all of the oustanding migrations against the database connection.
		$files = $this->getMigrationFiles($path);

		$ran = $this->repository->getRanMigrations($package);

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
			$this->runUp($output, $this->resolve($file), $pretend);

			$this->repository->logMigration($package, $file, $batch);
		}
	}

	/**
	 * Run "up" a migration instance.
	 *
	 * @param  Symfony\Component\Console\Output\OutputInterface  $output
	 * @param  object  $migration
	 * @param  bool    $pretend
	 * @return void
	 */
	protected function runUp($output, $migration, $pretend)
	{
		if ($pretend) return $this->pretendToRunUp($output, $migration);

		$migration->up();

		$output->writeln('<info>Migrated: '.get_class($migration).'</info>');
	}

	/**
	 * Pretend to run the "up" migrations.
	 *
	 * @param  Symfony\Component\Console\Output\OutputInterface  $output
	 * @param  object  $migration
	 * @return void
	 */
	protected function pretendToRunUp($output, $migration)
	{
		foreach ($this->getQueries($output, $migration, 'up') as $query)
		{
			$output->writeln("<info>$query</info>");
		}
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
		$connection = $this->resolveConnection($connection);

		return $connection->pretend(function() use ($migration, $method)
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