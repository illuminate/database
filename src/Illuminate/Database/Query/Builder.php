<?php namespace Illuminate\Database\Query;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Processors\Processor;

class Builder {

	/**
	 * The database connection instance.
	 *
	 * @var Illuminate\Database\Connection
	 */
	protected $connection;

	/**
	 * The database query grammar instance.
	 *
	 * @var Illuminate\Database\Query\Grammar
	 */
	protected $grammar;

	/**
	 * The database query post processor instance.
	 *
	 * @var Illuminate\Database\Query\Processors\Processor
	 */
	protected $processor;

	/**
	 * The table which the query is targeting.
	 *
	 * @var string
	 */
	public $from;

	/**
	 * The "offset" value of the query.
	 *
	 * @var int
	 */
	public $skip;

	/**
	 * The "limit" clause of the query.
	 *
	 * @var int
	 */
	public $take;

	/**
	 * Create a new query builder instance.
	 *
	 * @param  Illuminate\Database\Connection  $connection
	 * @param  Illuminate\Databaase\Query\Grammar  $grammar
	 * @param  Illuminate\Database\Query\Processors\Processor  $processor
	 * @return void
	 */
	public function __construct(Connection $connection,
                                Grammar $grammar,
                                Processor $processor)
	{
		$this->grammar = $grammar;
		$this->processor = $processor;
		$this->connection = $connection;
	}

	/**
	 * Set the table which the query is targeting.
	 *
	 * @param  string  $table
	 * @return Illuminate\Database\Query\Builder
	 */
	public function from($table)
	{
		$this->from = $table;

		return $this;
	}

	/**
	 * Set the "offset" value of the query.
	 *
	 * @param  int  $value
	 * @return Illuminate\Database\Query\Builder
	 */
	public function skip($value)
	{
		$this->skip = $value;

		return $this;
	}

	/**
	 * Set the "limit" value of the query.
	 *
	 * @param  int  $value
	 * @return Illuminate\Database\Query\Builder
	 */
	public function take($value)
	{
		$this->take = $value;

		return $this;
	}

	/**
	 * Execute the query and get the first result.
	 *
	 * @return mixed
	 */
	public function first()
	{
		$results = $this->take(1)->get();

		return count($results) > 0 ? reset($results) : null;
	}

	/**
	 * Execute the query as a "select" statement.
	 *
	 * @return array
	 */
	public function get()
	{
		// First we need to compile the query into actual SQL using the query grammar.
		// Once we have the SQL, we can execute it against the database connection
		// and run the results through the post-processor before returning out.
		$sql = $this->grammar->compileSelect($this);

		$results = $this->connection->select($sql, $this->bindings);

		return $this->processor->processSelect($this, $results);
	}

}