<?php namespace Illuminate\Database;

use Illuminate\Filesystem;
use Illuminate\Events\Dispatcher;

class Seeder {

	/**
	 * The filesystem instance.
	 *
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * The event dispatcher instance.
	 *
	 * @var Illuminate\Events\Dispatcher
	 */
	protected $events;

	/**
	 * Create a new database seeder instance.
	 *
	 * @param  Illuminate\Filesystem  $files
	 * @param  Illuminate\Events\Dispatcher  $events
	 * @return void
	 */
	public function __construct(Filesystem $files, Dispatcher $events = null)
	{
		$this->files = $files;
		$this->events = $events;
	}

	/**
	 * Seed the given connection from the given path.
	 *
	 * @param  Illuminate\Database\Connection  $connection
	 * @param  string  $path
	 * @return void
	 */
	public function seed(Connection $connection, $path)
	{
		foreach ($this->getFiles($path) as $file)
		{
			$records = $this->files->getRequire($file);

			// We'll grab the table name here, which could either come from the array or
			// from the filename itself. Then, we will simply insert the records into
			// the databases via a connection and fire an event noting the seeding.
			$table = $this->getTable($records, $file);

			$connection->table($table)->insert($records);

			if (isset($this->events))
			{
				$count = count($records);

				$this->events->fire('illuminate.seeding', array($table, $count));
			}
		}
	}

	/**
	 * Get all of the files at a given path.
	 *
	 * @param  string  $path
	 * @return array
	 */
	protected function getFiles($path)
	{
		$files = $this->files->glob($path.'/*.php');

		sort($files);

		return $files;
	}

	/**
	 * Get the table from the given records and file.
	 *
	 * @param  array   $records
	 * @param  string  $file
	 * @return string
	 */
	protected function getTable( & $records, $file)
	{
		$table = array_get($records, 'table', basename($file, '.php'));

		unset($records['table']);

		return $table;
	}

}