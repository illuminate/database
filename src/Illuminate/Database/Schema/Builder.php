<?php namespace Illuminate\Database\Schema;

use Closure;
use Illuminate\Database\Schema\Grammars\Grammar;

class Builder {

	/**
	 * The database connection instance.
	 *
	 * @var Illuminate\Database\Connection
	 */
	protected $connection;

	/**
	 * The schema grammar instance.
	 *
	 * @var Illuminate\Database\Schema\Grammars\Grammar
	 */
	protected $grammar;

	/**
	 * Create a new database Schema manager.
	 *
	 * @param  Illuminate\Database\Connection  $connection
	 * @param  Illuminate\Database\Schema\Grammars\Grammar  $grammar
	 * @return void
	 */
	public function __construct(Connection $connection, Grammar $grammar)
	{
		$this->grammar = $grammar;
		$this->connection = $connection;
	}

	/**
	 * Modify a table on the schema.
	 *
	 * @param  string   $table
	 * @param  Closure  $callback
	 * @return Illuminate\Database\Schema\Blueprint
	 */
	public function table($table, Closure $callback)
	{
		$this->build($this->createBlueprint($table, $callback));
	}

	/**
	 * Create a new table on the schema.
	 *
	 * @param  string   $table
	 * @param  Closure  $callback
	 * @return Illuminate\Database\Schema\Blueprint
	 */
	public function create($table, Closure $callback)
	{
		$this->build($this->createBlueprint($table, $callback)->create());
	}

	/**
	 * Drop a table from the schema.
	 *
	 * @param  string  $table
	 * @return Illuminate\Database\Schema\Blueprint
	 */
	public function drop($table)
	{
		$this->build($this->createBlueprint($table)->drop());
	}

	/**
	 * Rename a table on the schema.
	 *
	 * @param  string  $from
	 * @param  string  $to
	 * @return Illuminate\Database\Schema\Blueprint
	 */
	public function rename($from, $to)
	{
		$this->build($this->createBlueprint($from)->rename($to));
	}

	/**
	 * Execute the blueprint to build / modify the table.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @return void
	 */
	protected function build(Blueprint $blueprint)
	{
		$blueprint->build($this->connection, $this->grammar);
	}

	/**
	 * Create a new command set with a Closure.
	 *
	 * @param  string   $table
	 * @param  Closure  $callback
	 * @return Illuminate\Database\Schema\Blueprint
	 */
	protected function createBlueprint($table, Closure $callback = null)
	{
		return new Blueprint($table, $callback);
	}

	/**
	 * Get the database connection instance.
	 *
	 * @return Illuminate\Database\Connection
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 * Set the database connection instance.
	 *
	 * @param  Illuminate\Database\Connection
	 * @return Illuminate\Database\Schema
	 */
	public function setConnection(Connection $connection)
	{
		$this->connection = $connection;

		return $this;
	}

}