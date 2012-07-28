<?php namespace Illuminate\Database\Schema;

use Closure;

class Blueprint {

	/**
	 * The table the blueprint describes.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The columns that should be added to the table.
	 *
	 * @var array
	 */
	protected $columns = array();

	/**
	 * The commands that should be run for the table.
	 *
	 * @var array
	 */
	protected $commands = array();

	/**
	 * Create a new schema blueprint.
	 *
	 * @param  string   $table
	 * @param  Closure  $callback
	 * @return void
	 */
	public function __construct($table, Closure $callback = null)
	{
		$this->table = $table;

		if ( ! is_null($callback)) $callback($this);
	}

	/**
	 * Get the table the blueprint describes.
	 *
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * Get the columns that should be added.
	 *
	 * @return array
	 */
	public function getColumns()
	{
		return $this->columns;
	}

	/**
	 * Get the commands on the blueprint.
	 *
	 * @return array
	 */
	public function getCommands()
	{
		return $this->commands;
	}

}