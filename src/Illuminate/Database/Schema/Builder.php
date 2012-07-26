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
	 * The commands that have been generated.
	 *
	 * @var array
	 */
	protected $commands = array();

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
	 * @return Illuminate\Database\Schema\CommandSet
	 */
	public function table($table, Closure $callback)
	{
		return $this->createCommandSet($callback)->table($table);
	}

	/**
	 * Create a new table on the schema.
	 *
	 * @param  string   $table
	 * @param  Closure  $callback
	 * @return Illuminate\Database\Schema\CommandSet
	 */
	public function create($table, Closure $callback)
	{
		return $this->createCommandSet($callback)->table($table)->create();
	}

	/**
	 * Drop a table from the schema.
	 *
	 * @param  string  $table
	 * @return Illuminate\Database\Schema\CommandSet
	 */
	public function drop($table)
	{
		return $this->createCommandSet()->drop($table);
	}

	/**
	 * Rename a table on the schema.
	 *
	 * @param  string  $from
	 * @param  string  $to
	 * @return Illuminate\Database\Schema\CommandSet
	 */
	public function rename($from, $to)
	{
		return $this->createCommandSet()->rename($from, $to);
	}

	/**
	 * Create a new command set with a Closure.
	 *
	 * @param  Closure  $callback
	 * @return Illuminate\Database\Schema\CommandSet
	 */
	protected function createCommandSet(Closure $callback = null)
	{
		$this->commands[] = $commands = new CommandSet($callback);

		return $commands;
	}

	/**
	 * Run the given command set against the database.
	 *
	 * @param  Illuminate\Database\Schema\CommandSet  $commands
	 * @return void
	 */
	public function run(CommandSet $commands)
	{
		$sql = $this->grammar->toSql($commands);

		// Run the SQL...
	}

	/**
	 * Get the SQL that will be run by the command set.
	 *
	 * @param  Illuminate\Database\Schema\CommandSet  $commands
	 * @return array
	 */
	public function toSql(CommandSet $commands)
	{
		return $this->grammar->toSql($commands);
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