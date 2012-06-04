<?php namespace Illuminate\Database; use PDO;

abstract class Connection {

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
	 * Run a select statement against the database.
	 *
	 * @param  string  $sql
	 * @param  array   $bindings
	 * @return mixed
	 */
	public function selectOne($sql, $bindings = array())
	{
		$records = $this->select($sqll, $bindings);

		return count($records) > 0 ? reset($records) : null;
	}

	/**
	 * Run a select statement against the database.
	 *
	 * @param  string  $sql
	 * @param  array   $bindings
	 * @return array
	 */
	public function select($sql, $bindings = array())
	{
		$statement = $this->pdo->prepare($sql);

		$statement->execute($bindings);

		return $statement->fetchAll();
	}

	/**
	 * Run an insert statement against the database.
	 *
	 * @param  string  $sql
	 * @param  array   $bindings
	 * @return bool
	 */
	public function insert($sql, $bindings = array())
	{
		return $this->statement($sql, $bindings);
	}

	/**
	 * Run an update statement against the database.
	 *
	 * @param  string  $sql
	 * @param  array   $bindings
	 * @return int
	 */
	public function update($sql, $bindings = array())
	{
		return $this->affectingStatement($sql, $bindings);
	}

	/**
	 * Run a delete statement against the database.
	 *
	 * @param  string  $sql
	 * @param  array   $bindings
	 * @return int
	 */
	public function delete($sql, $bindings = array())
	{
		return $this->affectingStatement($sql, $bindings);
	}

	/**
	 * Execute an SQL statement and return the boolean result.
	 *
	 * @param  string  $sql
	 * @param  array   $bindings
	 * @return bool
	 */
	public function statement($sql, $bindings = array())
	{
		return $this->pdo->prepare($sql)->execute($bindings);
	}

	/**
	 * Run an SQL statement and get the number of rows affected.
	 *
	 * @param  string  $sql
	 * @param  array   $bindings
	 * @return int
	 */
	public function affectingStatement($sql, $bindings = array())
	{
		$statement = $this->pdo->prepare($sql);

		$statement->execute($bindings);

		return $statement->rowCount();
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

}