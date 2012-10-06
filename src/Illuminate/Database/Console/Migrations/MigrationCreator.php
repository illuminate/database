<?php namespace Illuminate\Database\Console\Migrations;

use Illuminate\Filesystem;

class MigrationCreator {

	/**
	 * The filesystem instance.
	 *
	 * @var Illuminate\Filesystem
	 */
	protected $files;

	/**
	 * Create a new migration creator instance.
	 *
	 * @param  Illuminate\Filesystem  $files
	 * @return void
	 */
	public function __construct(Filesystem $files)
	{
		$this->files = $files;
	}

	/**
	 * Create a new migration at the given path.
	 *
	 * @param  string  $name
	 * @param  string  $path
	 * @param  string  $table
	 * @param  bool    $create
	 * @return void
	 */
	public function create($name, $path, $table = null, $create = false)
	{
		//
	}

}