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
	 * The blueprints that have been generated.
	 *
	 * @var array
	 */
	protected $blueprints = array();

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
		return $this->createBlueprint($table, $callback);
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
		$blueprint = $this->createBlueprint($table, $callback);

		$blueprint->create();

		return $blueprint;
	}

	/**
	 * Drop a table from the schema.
	 *
	 * @param  string  $table
	 * @return Illuminate\Database\Schema\Blueprint
	 */
	public function drop($table)
	{
		$blueprint = $this->createBlueprint($table);

		$blueprint->drop();

		return $blueprint;
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
		$blueprint = $this->createBlueprint($from);

		$blueprint->rename($to);

		return $blueprint;
	}

	/**
	 * Run the blueprints that have been created.
	 *
	 * @return void
	 */
	public function flush($dryRun = false)
	{
		if ($dryRun) return $this->toSql();

		foreach ($this->blueprints as $blueprint)
		{
			$this->build($blueprint);
		}
	}

	/**
	 * Get all of the SQL for the created blueprints.
	 *
	 * @return array
	 */
	public function toSql()
	{
		$me = $this;

		return array_merge(array_map(function($blueprint) use ($me)
		{
			return $me->build($blueprint, true);

		}, $this->blueprints));
	}

	/**
	 * Run the given blueprint against the database.
	 *
	 * @param  Illuminate\Database\Schema\Blueprint  $blueprint
	 * @param  bool  $dryRun
	 * @return array|null
	 */
	public function build(Blueprint $blueprint, $dryRun = false)
	{
		return $blueprint->build($this->connection, $this->grammar, $dryRun);
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
		$this->blueprints[] = $blueprint = new Blueprint($table, $callback);

		return $blueprint;
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