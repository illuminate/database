<?php namespace Illuminate\Database\Query;

use Closure;
use Illuminate\Database\ConnectionInterface;

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
	 * @var Illuminate\Database\Query\Processor
	 */
	protected $processor;

	/**
	 * The current query value bindings.
	 *
	 * @var array
	 */
	protected $bindings = array();

	/**
	 * An aggregate function and column to be run.
	 *
	 * @var array
	 */
	public $aggregate;

	/**
	 * The columns that should be returned.
	 *
	 * @var array
	 */
	public $columns;

	/**
	 * Indicates if the query returns distinct results.
	 *
	 * @var bool
	 */
	public $distinct = false;

	/**
	 * The table which the query is targeting.
	 *
	 * @var string
	 */
	public $from;

	/**
	 * The table joins for the query.
	 *
	 * @var array
	 */
	public $joins;

	/**
	 * The where constraints for the query.
	 *
	 * @var array
	 */
	public $wheres;

	/**
	 * The groupings for the query.
	 *
	 * @var array
	 */
	public $groups;

	/**
	 * The having constraints for the query.
	 *
	 * @var array
	 */
	public $havings;

	/**
	 * The orderings for the query.
	 *
	 * @var array
	 */
	public $orders;

	/**
	 * The maximum number of records to return.
	 *
	 * @var int
	 */
	public $limit;

	/**
	 * The number of records to skip.
	 *
	 * @var int
	 */
	public $offset;

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
	 * All of the available clause operators.
	 *
	 * @var array
	 */
	protected $operators = array('=', '<', '>', '<=', '>=', 'like', 'not like');

	/**
	 * Create a new query builder instance.
	 *
	 * @param  Illuminate\Database\ConnectionInterface  $connection
	 * @param  Illuminate\Databaase\Query\Grammar  $grammar
	 * @param  Illuminate\Database\Query\Processor  $processor
	 * @return void
	 */
	public function __construct(ConnectionInterface $connection,
                                Grammar $grammar,
                                Processor $processor)
	{
		$this->grammar = $grammar;
		$this->processor = $processor;
		$this->connection = $connection;
	}

	/**
	 * Set the columns to be selected.
	 *
	 * @param  array  $columns
	 * @return Illuminate\Database\Query\Builder
	 */
	public function select($columns = array('*'))
	{
		$this->columns = is_array($columns) ? $columns : func_get_args();

		return $this;
	}

	/**
	 * Force the query to only return distinct results.
	 *
	 * @return Illuminate\Database\Query\Builder
	 */
	public function distinct()
	{
		$this->distinct = true;

		return $this;
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
	 * Add a join clause to the query.
	 *
	 * @param  string  $table
	 * @param  string  $first
	 * @param  string  $operator
	 * @param  string  $second
	 * @param  string  $type
	 * @return Illuminate\Database\Query\Builder
	 */
	public function join($table, $first, $operator = null, $second = null, $type = 'inner')
	{
		// If the first "column" of the join is really a Closure instance we are
		// trying to build a join with a complex "on" clause containing more
		// than one condition, so we'll add the join and call the Closure.
		if ($first instanceof Closure)
		{
			$this->joins[] = new JoinClause($type, $table);

			call_user_func($first, end($this->joins));
		}

		// If the column is just a string, we can assume the join simply has a
		// basic "on" clause with a single condition. So we will just build
		// the join clause instance and auto set the simple join clause. 
		else
		{
			$join = new JoinClause($type, $table);

			$join->on($first, $operator, $second);

			$this->joins[] = $join;
		}

		return $this;
	}

	/**
	 * Add a left join to the query.
	 *
	 * @param  string  $table
	 * @param  string  $first
	 * @param  string  $operator
	 * @param  string  $second
	 * @return Illuminate\Database\Query\Builder
	 */
	public function leftJoin($table, $first, $operator = null, $second = null)
	{
		return $this->join($table, $first, $operator, $second, 'left');
	}

	/**
	 * Add a basic where clause to the query.
	 *
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @param  string  $boolean
	 * @return Illuminate\Database\Query\Builder
	 */
	public function where($column, $operator = null, $value = null, $boolean = 'and')
	{
		// If the columns is actually a Closure instance, we will assume the developer
		// wants to begin a nested where statement which is wrapped in parenthesis.
		// We'll add that Closure to the query and return back out immediately.
		if ($column instanceof Closure)
		{
			return $this->whereNested($column, $boolean);
		}

		// If the given operator is not found in the list of valid operators we will
		// assume that the develoepr is just short-cutting the '=' operator and
		// we will set the operator to '=' and set the value appropriately.
		if ( ! in_array($operator, $this->operators))
		{
			list($value, $operator) = array($operator, '=');
		}

		// Now that we are working with just a simple query we can put the elements
		// in our array and add the query binding to our array of bindings that
		// will be bound to the SQL statements when it is finally executed.
		$type = 'Basic';

		$this->wheres[] = compact('type', 'column', 'operator', 'value', 'boolean');

		$this->bindings[] = $value;

		return $this;
	}

	/**
	 * Add an "or where" clause to the query.
	 *
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @return Illuminate\Database\Query\Builder
	 */
	public function orWhere($column, $operator = null, $value = null)
	{
		return $this->where($column, $operator, $value, 'or');
	}

	/**
	 * Add a nested where statement to the query.
	 *
	 * @param  Closure  $callback
	 * @param  string   $boolean
	 * @return Illuminate\Database\Query\Builder
	 */
	public function whereNested(Closure $callback, $boolean = 'and')
	{
		$type = 'Nested';

		$query = new Builder($this->connection, $this->grammar, $this->processor);

		// To handle nested queries we actually will create a brand new query instance
		// and pass it off to the Closure that we have. The Closure can then just
		// do whatever it wants to the quer and we'll store it for compiling.
		$query->from($this->from);

		call_user_func($callback, $query);

		if ( ! is_null($query->wheres))
		{
			$this->wheres[] = compact('type', 'query', 'boolean');
		}

		// Once we have let the Closure do its things we can gather the bindings on
		// the nested query builder and merge them into our bindings since they
		// need to be extracted out of the child and assigend to our array.
		$this->mergeBindings($query);

		return $this;
	}

	/**
	 * Add a "where in" clause to the query.
	 *
	 * @param  string  $column
	 * @param  array   $values
	 * @param  string  $boolean
	 * @param  bool    $not
	 * @return Illuminate\Database\Query\Builder
	 */
	public function whereIn($column, array $values, $boolean = 'and', $not = false)
	{
		$type = $not ? 'NotIn' : 'In';

		$this->wheres[] = compact('type', 'column', 'values', 'boolean');

		$this->bindings = array_merge($this->bindings, $values);

		return $this;
	}

	/**
	 * Add an "or where in" clause to the query.
	 *
	 * @param  string  $column
	 * @param  array   $values
	 * @param  mixed   $value
	 * @return Illuminate\Database\Query\Builder
	 */
	public function orWhereIn($column, array $values)
	{
		return $this->whereIn($column, $values, 'or');
	}

	/**
	 * Add a "where not in" clause to the query.
	 *
	 * @param  string  $column
	 * @param  array   $values
	 * @param  string  $boolean
	 * @return Illuminate\Database\Query\Builder
	 */
	public function whereNotIn($column, array $values, $boolean = 'and')
	{
		return $this->whereIn($column, $values, $boolean, true);
	}

	/**
	 * Add an "or where not in" clause to the query.
	 *
	 * @param  string  $column
	 * @param  array   $values
	 * @return Illuminate\Database\Query\Builder
	 */
	public function orWhereNotIn($column, array $values)
	{
		return $this->whereNotIn($column, $values, 'or');
	}	

	/**
	 * Add a "where null" clause to the query.
	 *
	 * @param  string  $column
	 * @param  string  $boolean
	 * @param  bool    $not
	 * @return Illuminate\Database\Query\Builder
	 */
	public function whereNull($column, $boolean = 'and', $not = false)
	{
		$type = $not ? 'NotNull' : 'Null';

		$this->wheres[] = compact('type', 'column', 'boolean');

		return $this;
	}

	/**
	 * Add an "or where null" clause to the query.
	 *
	 * @param  string  $column
	 * @return Illuminate\Database\Query\Builder
	 */
	public function orWhereNull($column)
	{
		return $this->whereNull($column, 'or');
	}

	/**
	 * Add a "where not null" clause to the query.
	 *
	 * @param  string  $column
	 * @param  string  $boolean
	 * @return Illuminate\Database\Query\Builder
	 */
	public function whereNotNull($column, $boolean = 'and')
	{
		return $this->whereNull($column, $boolean, true);
	}

	/**
	 * Add an "or where not null" clause to the query.
	 *
	 * @param  string  $column
	 * @return Illuminate\Database\Query\Builder
	 */
	public function orWhereNotNull($column)
	{
		return $this->whereNotNull($column, 'or');
	}

	/**
	 * Add a "group by" clause to the query.
	 *
	 * @param  dynamic  $columns
	 * @return Illuminate\Database\Query\Builder
	 */
	public function groupBy()
	{
		$this->groups = array_merge((array) $this->groups, func_get_args());

		return $this;
	}

	/**
	 * Add an "order by" clause to the query.
	 *
	 * @param  string  $column
	 * @param  string  $direction
	 * @return Illuminate\Database\Query\Builder
	 */
	public function orderBy($column, $direction = 'asc')
	{
		$this->orders[] = compact('column', 'direction');

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
		$this->offset = $value;

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
		$this->limit = $value;

		return $this;
	}

	/**
	 * Get the SQL representation of the query.
	 *
	 * @return string
	 */
	public function toSql()
	{
		return $this->grammar->compileSelect($this);
	}

	/**
	 * Execute a query for a single record by ID.
	 *
	 * @param  int    $id
	 * @param  array  $columns
	 * @return mixed
	 */
	public function find($id, $columns = array('*'))
	{
		return $this->where('id', '=', $id)->first($columns);
	}

	/**
	 * Execute the query and get the first result.
	 *
	 * @param  array   $columns
	 * @return mixed
	 */
	public function first($columns = array('*'))
	{
		$results = $this->take(1)->get($columns);

		return count($results) > 0 ? reset($results) : null;
	}

	/**
	 * Execute the query as a "select" statement.
	 *
	 * @param  array  $columns
	 * @return array
	 */
	public function get($columns = array('*'))
	{
		// If no columns have been specified for the select staement, we will set them
		// here to either the passed columns of the standard default of retrieving
		// all of the columns on the table using the wildcard column character.
		if (is_null($this->columns))
		{
			$this->columns = $columns;
		}

		$results = $this->connection->select($this->toSql(), $this->bindings);

		$this->processor->processSelect($this, $results);

		return $results;
	}

	/**
	 * Execute an aggregate function on the database.
	 *
	 * @param  string  $function
	 * @param  array   $columns
	 * @return mixed
	 */
	public function aggregate($function, $columns = array('*'))
	{
		$this->aggregate = compact('function', 'columns');

		$results = $this->get($columns);

		// Once we have executed the query, we will reset the aggregate property so
		// that more select queries can be executed against the database without
		// the aggregate value getting in the way when the grammar builds it.
		$this->aggregate = null;

		$result = (array) $results[0];

		return $result['aggregate'];
	}

	/**
	 * Insert a new record into the database.
	 *
	 * @param  array  $values
	 * @return bool
	 */
	public function insert(array $values)
	{
		if ( ! is_array(reset($values))) $values = array($values);

		$bindings = array();

		// We treat every insert like a batch insert so we can easily insert each
		// of the records into the database consistently. This just makes it
		// much easier on the grammar side to just handle one situation.
		foreach ($values as $record)
		{
			$bindings = array_merge($bindings, array_values($record));
		}

		$sql = $this->grammar->compileInsert($this, $bindings);

		return $this->connection->insert($sql, $bindings);
	}

	/**
	 * Insert a new record and get the value of the primary key.
	 *
	 * @param  array  $values
	 * @return int
	 */
	public function insertGetId(array $values)
	{
		$sql = $this->grammar->compileInsertGetId($this, $values);

		$result = $this->connection->insert($sql, $values);

		return $this->processor->processInsertGetId($this, $result);
	}

	/**
	 * Update a record in the database.
	 *
	 * @param  array  $values
	 * @return int
	 */
	public function update(array $values)
	{
		$values = array_merge($values, $this->bindings);

		$sql = $this->grammar->compileUpdate($this, $values);

		return $this->connection->update($sql, $values);
	}

	/**
	 * Delete a record from the database.
	 *
	 * @param  array  $values
	 * @return int
	 */
	public function delete($id = null)
	{
		// If an ID is passed to the method, we will set the where clause to check
		// the ID to allow developers to simply and quickly remove a single row
		// from their database without manually specifying the where clauses.
		if ( ! is_null($id))
		{
			$this->where('id', '=', $id);
		}

		$sql = $this->grammar->compileDelete($this);

		return $this->connection->delete($sql, $this->bindings);
	}

	/**
	 * Get the current query value bindings.
	 *
	 * @return array
	 */
	public function getBindings()
	{
		return $this->bindings;
	}

	/**
	 * Merge an array of bindings into our bindings.
	 *
	 * @param  Illuminate\Database\Query\Builder  $query
	 * @return void
	 */
	public function mergeBindings(Builder $query)
	{
		$this->bindings = array_merge($this->bindings, $query->bindings);
	}

	/**
	 * Get the database connection instance.
	 *
	 * @return Illuminate\Database\ConnectionInterface
	 */
	public function getConnection()
	{
		return $this->connection;
	}

}