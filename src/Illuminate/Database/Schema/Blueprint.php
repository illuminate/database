<?php namespace Illuminate\Database\Schema;

use Closure;
use Illuminate\Support\Fluent;

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
	 * Indicate that the table needs to be created.
	 *
	 * @return Illuminate\Support\Fluent
	 */
	public function create()
	{
		return $this->addCommand('create');
	}

	/**
	 * Indicate that the table should be dropped.
	 *
	 * @return Illuminate\Support\Fluent
	 */
	public function drop()
	{
		return $this->addCommand('drop');
	}

	/**
	 * Rename the table to a given name.
	 *
	 * @param  string  $to
	 * @return Illuminate\Support\Fluent
	 */
	public function rename($to)
	{
		return $this->addCommand('rename', compact('to'));
	}

	/**
	 * Create a new auto-incrementing column on the table.
	 *
	 * @param  string  $column
	 * @return Illuminate\Support\Fluent
	 */
	public function increments($column)
	{
		return $this->integer($column, true);
	}

	/**
	 * Create a new string column on the table.
	 *
	 * @param  string  $column
	 * @param  int  $length
	 * @return Illuminate\Support\Fluent
	 */
	public function string($column, $length = 255)
	{
		return $this->addColumn('string', $column, compact('length'));
	}

	/**
	 * Create a new text column on the table.
	 *
	 * @param  string  $column
	 * @return Illuminate\Support\Fluent
	 */
	public function text($column)
	{
		return $this->addColumn('text', $column);
	}

	/**
	 * Create a new integer column on the table.
	 *
	 * @param  string  $column
	 * @return Illuminate\Support\Fluent
	 */
	public function integer($column, $autoIncrement = false)
	{
		return $this->addColumn('integer', $column, compact('autoIncrement'));
	}

	/**
	 * Create a new float column on the table.
	 *
	 * @param  string  $column
	 * @param  int     $total
	 * @param  int     $places
	 * @return Illuminate\Support\Fluent
	 */
	public function float($column, $total, $places)
	{
		return $this->addColumn('float', $column, compact('total', 'places'));
	}

	/**
	 * Create a new decimal column on the table.
	 *
	 * @param  string  $column
	 * @param  int     $total
	 * @param  int     $places
	 * @return Illuminate\Support\Fluent
	 */
	public function decimal($column, $total, $places)
	{
		return $this->addColumn('decimal', $column, compact('total', 'places'));
	}

	/**
	 * Create a new boolean column on the table.
	 *
	 * @param  string  $column
	 * @return Illuminate\Support\Fluent
	 */
	public function boolean($column)
	{
		return $this->addColumn('boolean', $column);
	}

	/**
	 * Create a new date column on the table.
	 *
	 * @param  string  $column
	 * @return Illuminate\Support\Fluent
	 */
	public function date($column)
	{
		return $this->addColumn('date', $column);
	}

	/**
	 * Create a new date-time column on the table.
	 *
	 * @param  string  $column
	 * @return Illuminate\Support\Fluent
	 */
	public function dateTime($column)
	{
		return $this->addColumn('dateTime', $column);
	}

	/**
	 * Create a new time column on the table.
	 *
	 * @param  string  $column
	 * @return Illuminate\Support\Fluent
	 */
	public function time($column)
	{
		return $this->addColumn('time', $column);
	}

	/**
	 * Create a new timestamp column on the table.
	 *
	 * @param  string  $column
	 * @return Illuminate\Support\Fluent
	 */
	public function timestamp($column)
	{
		return $this->addColumn('timestamp', $column);
	}

	/**
	 * Add creation and update timestamps to the table.
	 *
	 * @return void
	 */
	public function timestamps()
	{
		$this->timestamp('created_at');

		$this->timestamp('updated_at');
	}

	/**
	 * Create a new binary column on the table.
	 *
	 * @param  string  $column
	 * @return Illuminate\Support\Fluent
	 */
	public function binary($column)
	{
		return $this->addColumn('binary', $column);
	}

	/**
	 * Add a new column to the blueprint.
	 *
	 * @param  string  $type
	 * @param  string  $name
	 * @param  array   $parameters
	 * @return Illuminate\Support\Fluent
	 */
	protected function addColumn($type, $name, array $parameters = array())
	{
		$attributes = array_merge(compact('type', 'name'), $parameters);

		$this->columns[] = $column = new Fluent($attributes);

		return $column;
	}

	/**
	 * Add a new command to the blueprint.
	 *
	 * @param  string  $name
	 * @param  array  $parameters
	 * @return Illuminate\Support\Fluent
	 */
	protected function addCommand($name, array $parameters = array())
	{
		$attributes = array_merge(compact('name'), $parameters);

		$this->commands[] = $command = new Fluent($attributes);

		return $command;
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