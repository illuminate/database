<?php namespace Illuminate\Database; use PDO, Closure;

abstract class Connection implements ConnectionInterface {

	/**
	 * The active PDO connection.
	 *
	 * @var PDO
	 */
	protected $pdo;

	/**
	 * The query grammar implementation.
	 *
	 * @var Illuminate\Database\Query\Grammars\Grammar
	 */
	protected $queryGrammar;

	/**
	 * All of the queries run against the connection.
	 *
	 * @var array
	 */
	protected $queryLog = array();

	/**
	 * Create a new database connection instance.
	 *
	 * @param  PDO   $pdo
	 * @return void
	 */
	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}

	/**
	 * Set the query grammar to the default implementation.
	 *
	 * @return void
	 */
	public function useDefaultQueryGrammar()
	{
		$this->queryGrammar = $this->getDefaultQueryGrammar();
	}

	/**
	 * Get the default query grammar instance.
	 *
	 * @return Illuminate\Database\Query\Grammars\Grammar
	 */
	abstract protected function getDefaultQueryGrammar();

	/**
	 * Run a select statement and return a single result.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return mixed
	 */
	public function selectOne($query, $bindings = array())
	{
		$records = $this->select($query, $bindings);

		return count($records) > 0 ? reset($records) : null;
	}

	/**
	 * Run a select statement against the database.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return array
	 */
	public function select($query, $bindings = array())
	{
		return $this->run($query, $bindings, function($me, $query, $bindings)
		{
			// For select statements, we'll simply execute the query and return an array
			// of the database result set. Each element in the array will be a single
			// row from the database table, and may either be an array or object.
			$statement = $me->getPdo()->prepare($query);

			$statement->execute($bindings);

			return $statement->fetchAll();
		});
	}

	/**
	 * Run an insert statement against the database.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return bool
	 */
	public function insert($query, $bindings = array())
	{
		return $this->statement($query, $bindings);
	}

	/**
	 * Run an update statement against the database.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return int
	 */
	public function update($query, $bindings = array())
	{
		return $this->affectingStatement($query, $bindings);
	}

	/**
	 * Run a delete statement against the database.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return int
	 */
	public function delete($query, $bindings = array())
	{
		return $this->affectingStatement($query, $bindings);
	}

	/**
	 * Execute an SQL statement and return the boolean result.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return bool
	 */
	public function statement($query, $bindings = array())
	{
		return $this->run($query, $bindings, function($me, $query, $bindings)
		{
			return $me->getPdo()->prepare($query)->execute($bindings);
		});
	}

	/**
	 * Run an SQL statement and get the number of rows affected.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return int
	 */
	public function affectingStatement($query, $bindings = array())
	{
		return $this->run($query, $bindings, function($me, $query, $bindings)
		{
			// For update or delete statements, we want to get the number of rows affected
			// by the statement and return that back to the developer. We'll first need
			// to execute the statement and then we'll use PDO to fetch the affected.
			$statement = $me->getPdo()->prepare($query);

			$statement->execute($bindings);

			return $statement->rowCount();
		});
	}

	/**
	 * Execute a Closure within a transaction.
	 *
	 * @param  Closure  $callback
	 * @return mixed
	 */
	public function transaction(Closure $callback)
	{
		$this->pdo->beginTransaction();

		// We'll simply execute the given callback within a try / catch block
		// and if we catch any exceptions we can rollback the transaction
		// so that none of the changes are persisted to the database.
		try
		{
			$result = $callback($this);

			$this->pdo->commit();
		}

		// If we catch an exception, we will roll back so nothing gets messed
		// up in the database. Then we'll re-throw the exception so it can
		// be handled how the developer sees fit for their application.
		catch (\Exception $e)
		{
			$this->pdo->rollBack();

			throw $e;
		}

		return $result;
	}

	/**
	 * Run a SQL statement and log its execution context.
	 *
	 * @param  string   $query
	 * @param  array    $bindings
	 * @param  Closure  $callback
	 * @return mixed
	 */
	protected function run($query, $bindings, Closure $callback)
	{
		$start = microtime(true);

		// To execute the statement, we'll simply call the callback, which will actually
		// run the SQL against the PDO connection. Then we can calculate the time it
		// took to execute and log the query SQL, bindings and time in our memory.
		$result = $callback($this, $query, $bindings);

		$time = number_format((microtime(true) - $start) * 1000, 2);

		$this->logQuery($query, $bindings, $time);

		return $result;
	}

	/**
	 * Log a query in the connection's query log.
	 *
	 * @param  string  $query
	 * @param  array   $bindings
	 * @return void
	 */
	protected function logQuery($query, $bindings, $time)
	{
		$this->queryLog[] = compact('query', 'bindings', 'time');
	}

	/**
	 * Get the currently used PDO connection.
	 *
	 * @return PDO
	 */
	public function getPdo()
	{
		return $this->pdo;
	}

	/**
	 * Get the query grammar used by the connection.
	 *
	 * @return Illuminate\Database\Query\Grammars\Grammar
	 */
	public function getQueryGrammar()
	{
		return $this->queryGrammar;
	}

	/**
	 * Set the query grammar used by the connection.
	 *
	 * @param  Illuminate\Database\Query\Grammars\Grammar
	 * @return void
	 */
	public function setQueryGrammar(Query\Grammars\Grammar $grammar)
	{
		$this->queryGrammar = $grammar;
	}

	/**
	 * Get the connection query log.
	 *
	 * @return array
	 */
	public function getQueryLog()
	{
		return $this->queryLog;
	}

}