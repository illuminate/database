<?php namespace Illuminate\Database\Console\Migrations;

use Closure;
use Illuminate\Database\Connection;

class DatabaseMigrationRepository implements MigrationRepositoryInterface {

	/**
	 * The database connection instance.
	 *
	 * @var Illuminate\Database\Connection|Closure
	 */
	protected $connection;

	/**
	 * The name of the migration table.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * Create a new database migration repository instance.
	 *
	 * @param  Illuminate\Database\Connection|Closure  $connection
	 * @return void
	 */
	public function __construct($connection, $table)
	{
		$this->table = $table;
		$this->connection = $connection;
	}

	/**
	 * Get the ran migrations for a given package.
	 *
	 * @param  string  $package
	 * @return array
	 */
	public function getRan($package)
	{
		return $this->table()->where('package', $package)->lists('migration');
	}

	/**
	 * Get the last migration batch.
	 *
	 * @return array
	 */
	public function getLast()
	{
		$query = $this->table()->where('batch', $this->getLastBatchNumber());

		return $query->orderBy('migration', 'desc')->get();
	}

	/**
	 * Log that a migration was run.
	 *
	 * @param  string  $package
	 * @param  string  $file
	 * @param  int     $batch
	 * @return void
	 */
	public function log($package, $file, $batch)
	{
		$record = array('package' => $package, 'migration' => $file, 'batch' => $batch);

		$this->table()->insert($record);
	}

	/**
	 * Remove that a migration from the log.
	 *
	 * @param  StdClass  $migration
	 * @return void
	 */
	public function delete($migration)
	{
		$query = $this->table()->where('migration', $migration->migration);

		$query->where('package', $migration->package)->delete();
	}

	/**
	 * Get the next migration batch number.
	 *
	 * @return int
	 */
	public function getNextBatchNumber()
	{
		return $this->getLastBatchNumber() + 1;
	}

	/**
	 * Get the last migration batch number.
	 *
	 * @return int
	 */
	public function getLastBatchNumber()
	{
		return $this->table()->max('batch');
	}

	/**
	 * Create the migration repository data store.
	 *
	 * @return void
	 */
	public function createRepository()
	{
		$schema = $this->getConnection()->getSchema();

		$schema->create($this->table, function($table)
		{
			// The migrations table is responsible for keeping track of which of the
			// migrations have actually run for the application. We'll create the
			// table to hold the migration and package as well as the batch ID.
			$table->string('migration');

			$table->string('package');

			$table->integer('batch');
		});
	}

	/**
	 * Get a query builder for the migration table.
	 *
	 * @return Illuminate\Database\Query\Builder
	 */
	protected function table()
	{
		return $this->getConnection()->table($this->table);
	}

	/**
	 * Resolve the database connection instance.
	 *
	 * @return Illuminate\Database\Connection
	 */
	public function getConnection()
	{
		if ($this->connection instanceof Closure)
		{
			$this->connection = call_user_func($this->connection);
		}

		return $this->connection;
	}

}