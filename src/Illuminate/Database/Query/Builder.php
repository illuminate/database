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
	}

}