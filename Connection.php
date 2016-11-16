<?php

namespace Illuminate\Database;

use Closure;
use DateTimeInterface;
use Doctrine\DBAL\Connection as DoctrineConnection;
use Exception;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Query\Grammars\Grammar as QueryGrammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Support\Arr;
use LogicException;
use PDO;
use RuntimeException;
use Throwable;

class Connection implements ConnectionInterface
{
    use DetectsDeadlocks, DetectsLostConnections;

    /**
     * The active PDO connection.
     *
     * @var PDO
     */
    protected $pdo;

    /**
     * The active PDO connection used for reads.
     *
     * @var PDO
     */
    protected $readPdo;

    /**
     * The reconnector instance for the connection.
     *
     * @var callable
     */
    protected $reconnector;

    /**
     * The query grammar implementation.
     *
     * @var \Illuminate\Database\Query\Grammars\Grammar
     */
    protected $queryGrammar;

    /**
     * The schema grammar implementation.
     *
     * @var \Illuminate\Database\Schema\Grammars\Grammar
     */
    protected $schemaGrammar;

    /**
     * The query post processor implementation.
     *
     * @var \Illuminate\Database\Query\Processors\Processor
     */
    protected $postProcessor;

    /**
     * The event dispatcher instance.
     *
     * @var \Illuminate\Contracts\Events\Dispatcher
     */
    protected $events;

    /**
     * The default fetch mode of the connection.
     *
     * @var int
     */
    protected $fetchMode = PDO::FETCH_OBJ;

    /**
     * The argument for the fetch mode.
     *
     * @var mixed
     */
    protected $fetchArgument;

    /**
     * The constructor arguments for the PDO::FETCH_CLASS fetch mode.
     *
     * @var array
     */
    protected $fetchConstructorArgument = [];

    /**
     * The number of active transactions.
     *
     * @var int
     */
    protected $transactions = 0;

    /**
     * All of the queries run against the connection.
     *
     * @var array
     */
    protected $queryLog = [];

    /**
     * Indicates whether queries are being logged.
     *
     * @var bool
     */
    protected $loggingQueries = false;

    /**
     * Indicates if the connection is in a "dry run".
     *
     * @var bool
     */
    protected $pretending = false;

    /**
     * The name of the connected database.
     *
     * @var string
     */
    protected $database;

    /**
     * The instance of Doctrine connection.
     *
     * @var \Doctrine\DBAL\Connection
     */
    protected $doctrineConnection;

    /**
     * The table prefix for the connection.
     *
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * The database connection configuration options.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Create a new database connection instance.
     *
     * @param \PDO|\Closure $pdo
     * @param string        $database
     * @param string        $tablePrefix
     * @param array         $config
     *
     * @return void
     */
    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        $this->pdo = $pdo;

        // First we will setup the default properties. We keep track of the DB
        // name we are connected to since it is needed when some reflective
        // type commands are run such as checking whether a table exists.
        $this->database = $database;

        $this->tablePrefix = $tablePrefix;

        $this->config = $config;

        // We need to initialize a query grammar and the query post processors
        // which are both very important parts of the database abstractions
        // so we initialize these to their default values while starting.
        $this->useDefaultQueryGrammar();

        $this->useDefaultPostProcessor();
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
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar();
    }

    /**
     * Set the schema grammar to the default implementation.
     *
     * @return void
     */
    public function useDefaultSchemaGrammar()
    {
        $this->schemaGrammar = $this->getDefaultSchemaGrammar();
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        //
    }

    /**
     * Set the query post processor to the default implementation.
     *
     * @return void
     */
    public function useDefaultPostProcessor()
    {
        $this->postProcessor = $this->getDefaultPostProcessor();
    }

    /**
     * Get the default post processor instance.
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new Processor();
    }

    /**
     * Get a schema builder instance for the connection.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new SchemaBuilder($this);
    }

    /**
     * Begin a fluent query against a database table.
     *
     * @param string $table
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function table($table)
    {
        return $this->query()->from($table);
    }

    /**
     * Get a new query builder instance.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function query()
    {
        return new QueryBuilder(
            $this, $this->getQueryGrammar(), $this->getPostProcessor()
        );
    }

    /**
     * Get a new raw query expression.
     *
     * @param mixed $value
     *
     * @return \Illuminate\Database\Query\Expression
     */
    public function raw($value)
    {
        return new Expression($value);
    }

    /**
     * Run a select statement and return a single result.
     *
     * @param string $query
     * @param array  $bindings
     *
     * @return mixed
     */
    public function selectOne($query, $bindings = [])
    {
        $records = $this->select($query, $bindings);

        return count($records) > 0 ? reset($records) : null;
    }

    /**
     * Run a select statement against the database.
     *
     * @param string $query
     * @param array  $bindings
     *
     * @return array
     */
    public function selectFromWriteConnection($query, $bindings = [])
    {
        return $this->select($query, $bindings, false);
    }

    /**
     * Run a select statement against the database.
     *
     * @param string $query
     * @param array  $bindings
     * @param bool   $useReadPdo
     *
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run($query, $bindings, function ($me, $query, $bindings) use ($useReadPdo) {
            if ($me->pretending()) {
                return [];
            }

            // For select statements, we'll simply execute the query and return an array
            // of the database result set. Each element in the array will be a single
            // row from the database table, and will either be an array or objects.
            $statement = $this->getPdoForSelect($useReadPdo)->prepare($query);

            $me->bindValues($statement, $me->prepareBindings($bindings));

            $statement->execute();

            $fetchMode = $me->getFetchMode();
            $fetchArgument = $me->getFetchArgument();
            $fetchConstructorArgument = $me->getFetchConstructorArgument();

            if ($fetchMode === PDO::FETCH_CLASS && !isset($fetchArgument)) {
                $fetchArgument = 'StdClass';
                $fetchConstructorArgument = null;
            }

            return isset($fetchArgument)
                ? $statement->fetchAll($fetchMode, $fetchArgument, $fetchConstructorArgument)
                : $statement->fetchAll($fetchMode);
        });
    }

    /*
     * Run a select statement against the database and returns a generator.
     *
     * @param  string  $query
     * @param  array  $bindings
     * @param  bool  $useReadPdo
     * @return \Generator
     */
    public function cursor($query, $bindings = [], $useReadPdo = true)
    {
        $statement = $this->run($query, $bindings, function ($me, $query, $bindings) use ($useReadPdo) {
            if ($me->pretending()) {
                return [];
            }

            $statement = $this->getPdoForSelect($useReadPdo)->prepare($query);

            $fetchMode = $me->getFetchMode();
            $fetchArgument = $me->getFetchArgument();
            $fetchConstructorArgument = $me->getFetchConstructorArgument();

            if ($fetchMode === PDO::FETCH_CLASS && !isset($fetchArgument)) {
                $fetchArgument = 'StdClass';
                $fetchConstructorArgument = null;
            }

            if (isset($fetchArgument)) {
                $statement->setFetchMode($fetchMode, $fetchArgument, $fetchConstructorArgument);
            } else {
                $statement->setFetchMode($fetchMode);
            }

            $me->bindValues($statement, $me->prepareBindings($bindings));

            $statement->execute();

            return $statement;
        });

        while ($record = $statement->fetch()) {
            yield $record;
        }
    }

    /**
     * Bind values to their parameters in the given statement.
     *
     * @param \PDOStatement $statement
     * @param array         $bindings
     *
     * @return void
     */
    public function bindValues($statement, $bindings)
    {
        foreach ($bindings as $key => $value) {
            $statement->bindValue(
                is_string($key) ? $key : $key + 1, $value,
                is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR
            );
        }
    }

    /**
     * Get the PDO connection to use for a select query.
     *
     * @param bool $useReadPdo
     *
     * @return \PDO
     */
    protected function getPdoForSelect($useReadPdo = true)
    {
        return $useReadPdo ? $this->getReadPdo() : $this->getPdo();
    }

    /**
     * Run an insert statement against the database.
     *
     * @param string $query
     * @param array  $bindings
     *
     * @return bool
     */
    public function insert($query, $bindings = [])
    {
        return $this->statement($query, $bindings);
    }

    /**
     * Run an update statement against the database.
     *
     * @param string $query
     * @param array  $bindings
     *
     * @return int
     */
    public function update($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Run a delete statement against the database.
     *
     * @param string $query
     * @param array  $bindings
     *
     * @return int
     */
    public function delete($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param string $query
     * @param array  $bindings
     *
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($me, $query, $bindings) {
            if ($me->pretending()) {
                return true;
            }

            $statement = $this->getPdo()->prepare($query);

            $this->bindValues($statement, $me->prepareBindings($bindings));

            return $statement->execute();
        });
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param string $query
     * @param array  $bindings
     *
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($me, $query, $bindings) {
            if ($me->pretending()) {
                return 0;
            }

            // For update or delete statements, we want to get the number of rows affected
            // by the statement and return that back to the developer. We'll first need
            // to execute the statement and then we'll use PDO to fetch the affected.
            $statement = $me->getPdo()->prepare($query);

            $this->bindValues($statement, $me->prepareBindings($bindings));

            $statement->execute();

            return $statement->rowCount();
        });
    }

    /**
     * Run a raw, unprepared query against the PDO connection.
     *
     * @param string $query
     *
     * @return bool
     */
    public function unprepared($query)
    {
        return $this->run($query, [], function ($me, $query) {
            if ($me->pretending()) {
                return true;
            }

            return (bool) $me->getPdo()->exec($query);
        });
    }

    /**
     * Prepare the query bindings for execution.
     *
     * @param array $bindings
     *
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar();

        foreach ($bindings as $key => $value) {
            // We need to transform all instances of DateTimeInterface into the actual
            // date string. Each query grammar maintains its own date string format
            // so we'll just ask the grammar for the format to get from the date.
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            } elseif ($value === false) {
                $bindings[$key] = 0;
            }
        }

        return $bindings;
    }

    /**
     * Execute a Closure within a transaction.
     *
     * @param \Closure $callback
     * @param int      $attempts
     *
     * @throws \Exception|\Throwable
     *
     * @return mixed
     */
    public function transaction(Closure $callback, $attempts = 1)
    {
        for ($a = 1; $a <= $attempts; $a++) {
            $this->beginTransaction();

            // We'll simply execute the given callback within a try / catch block
            // and if we catch any exception we can rollback the transaction
            // so that none of the changes are persisted to the database.
            try {
                $result = $callback($this);

                $this->commit();
            }

            // If we catch an exception, we will roll back so nothing gets messed
            // up in the database. Then we'll re-throw the exception so it can
            // be handled how the developer sees fit for their applications.
            catch (Exception $e) {
                $this->rollBack();

                if ($this->causedByDeadlock($e) && $a < $attempts) {
                    continue;
                }

                throw $e;
            } catch (Throwable $e) {
                $this->rollBack();

                throw $e;
            }

            return $result;
        }
    }

    /**
     * Start a new database transaction.
     *
     * @throws Exception
     *
     * @return void
     */
    public function beginTransaction()
    {
        if ($this->transactions == 0) {
            try {
                $this->pdo->beginTransaction();
            } catch (Exception $e) {
                if ($this->causedByLostConnection($e)) {
                    $this->reconnect();
                    $this->pdo->beginTransaction();
                } else {
                    throw $e;
                }
            }
        } elseif ($this->transactions >= 1 && $this->queryGrammar->supportsSavepoints()) {
            $this->pdo->exec(
                $this->queryGrammar->compileSavepoint('trans'.($this->transactions + 1))
            );
        }

        ++$this->transactions;

        $this->fireConnectionEvent('beganTransaction');
    }

    /**
     * Commit the active database transaction.
     *
     * @return void
     */
    public function commit()
    {
        if ($this->transactions == 1) {
            $this->getPdo()->commit();
        }

        $this->transactions = max(0, $this->transactions - 1);

        $this->fireConnectionEvent('committed');
    }

    /**
     * Rollback the active database transaction.
     *
     * @param int|null $toLevel
     *
     * @return void
     */
    public function rollBack($toLevel = null)
    {
        if (is_null($toLevel)) {
            $toLevel = $this->transactions - 1;
        }

        if ($toLevel < 0 || $toLevel >= $this->transactions) {
            return;
        }

        if ($toLevel == 0) {
            $this->getPdo()->rollBack();
        } elseif ($this->queryGrammar->supportsSavepoints()) {
            $this->getPdo()->exec(
                $this->queryGrammar->compileSavepointRollBack('trans'.($toLevel + 1))
            );
        }

        $this->transactions = $toLevel;

        $this->fireConnectionEvent('rollingBack');
    }

    /**
     * Get the number of active transactions.
     *
     * @return int
     */
    public function transactionLevel()
    {
        return $this->transactions;
    }

    /**
     * Execute the given callback in "dry run" mode.
     *
     * @param \Closure $callback
     *
     * @return array
     */
    public function pretend(Closure $callback)
    {
        $loggingQueries = $this->loggingQueries;

        $this->enableQueryLog();

        $this->pretending = true;

        $this->queryLog = [];

        // Basically to make the database connection "pretend", we will just return
        // the default values for all the query methods, then we will return an
        // array of queries that were "executed" within the Closure callback.
        $callback($this);

        $this->pretending = false;

        $this->loggingQueries = $loggingQueries;

        return $this->queryLog;
    }

    /**
     * Run a SQL statement and log its execution context.
     *
     * @param string   $query
     * @param array    $bindings
     * @param \Closure $callback
     *
     * @throws \Illuminate\Database\QueryException
     *
     * @return mixed
     */
    protected function run($query, $bindings, Closure $callback)
    {
        $this->reconnectIfMissingConnection();

        $start = microtime(true);

        // Here we will run this query. If an exception occurs we'll determine if it was
        // caused by a connection that has been lost. If that is the cause, we'll try
        // to re-establish connection and re-run the query with a fresh connection.
        try {
            $result = $this->runQueryCallback($query, $bindings, $callback);
        } catch (QueryException $e) {
            if ($this->transactions >= 1) {
                throw $e;
            }

            $result = $this->tryAgainIfCausedByLostConnection(
                $e, $query, $bindings, $callback
            );
        }

        // Once we have run the query we will calculate the time that it took to run and
        // then log the query, bindings, and execution time so we will report them on
        // the event that the developer needs them. We'll log time in milliseconds.
        $time = $this->getElapsedTime($start);

        $this->logQuery($query, $bindings, $time);

        return $result;
    }

    /**
     * Run a SQL statement.
     *
     * @param string   $query
     * @param array    $bindings
     * @param \Closure $callback
     *
     * @throws \Illuminate\Database\QueryException
     *
     * @return mixed
     */
    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        // To execute the statement, we'll simply call the callback, which will actually
        // run the SQL against the PDO connection. Then we can calculate the time it
        // took to execute and log the query SQL, bindings and time in our memory.
        try {
            $result = $callback($this, $query, $bindings);
        }

        // If an exception occurs when attempting to run a query, we'll format the error
        // message to include the bindings with SQL, which will make this exception a
        // lot more helpful to the developer instead of just the database's errors.
        catch (Exception $e) {
            throw new QueryException(
                $query, $this->prepareBindings($bindings), $e
            );
        }

        return $result;
    }

    /**
     * Handle a query exception that occurred during query execution.
     *
     * @param \Illuminate\Database\QueryException $e
     * @param string                              $query
     * @param array                               $bindings
     * @param \Closure                            $callback
     *
     * @throws \Illuminate\Database\QueryException
     *
     * @return mixed
     */
    protected function tryAgainIfCausedByLostConnection(QueryException $e, $query, $bindings, Closure $callback)
    {
        if ($this->causedByLostConnection($e->getPrevious())) {
            $this->reconnect();

            return $this->runQueryCallback($query, $bindings, $callback);
        }

        throw $e;
    }

    /**
     * Disconnect from the underlying PDO connection.
     *
     * @return void
     */
    public function disconnect()
    {
        $this->setPdo(null)->setReadPdo(null);
    }

    /**
     * Reconnect to the database.
     *
     * @throws \LogicException
     *
     * @return void
     */
    public function reconnect()
    {
        if (is_callable($this->reconnector)) {
            return call_user_func($this->reconnector, $this);
        }

        throw new LogicException('Lost connection and no reconnector available.');
    }

    /**
     * Reconnect to the database if a PDO connection is missing.
     *
     * @return void
     */
    protected function reconnectIfMissingConnection()
    {
        if (is_null($this->getPdo()) || is_null($this->getReadPdo())) {
            $this->reconnect();
        }
    }

    /**
     * Log a query in the connection's query log.
     *
     * @param string     $query
     * @param array      $bindings
     * @param float|null $time
     *
     * @return void
     */
    public function logQuery($query, $bindings, $time = null)
    {
        if (isset($this->events)) {
            $this->events->fire(new Events\QueryExecuted(
                $query, $bindings, $time, $this
            ));
        }

        if ($this->loggingQueries) {
            $this->queryLog[] = compact('query', 'bindings', 'time');
        }
    }

    /**
     * Register a database query listener with the connection.
     *
     * @param \Closure $callback
     *
     * @return void
     */
    public function listen(Closure $callback)
    {
        if (isset($this->events)) {
            $this->events->listen(Events\QueryExecuted::class, $callback);
        }
    }

    /**
     * Fire an event for this connection.
     *
     * @param string $event
     *
     * @return void
     */
    protected function fireConnectionEvent($event)
    {
        if (!isset($this->events)) {
            return;
        }

        switch ($event) {
            case 'beganTransaction':
                return $this->events->fire(new Events\TransactionBeginning($this));
            case 'committed':
                return $this->events->fire(new Events\TransactionCommitted($this));
            case 'rollingBack':
                return $this->events->fire(new Events\TransactionRolledBack($this));
        }
    }

    /**
     * Get the elapsed time since a given starting point.
     *
     * @param int $start
     *
     * @return float
     */
    protected function getElapsedTime($start)
    {
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Is Doctrine available?
     *
     * @return bool
     */
    public function isDoctrineAvailable()
    {
        return class_exists('Doctrine\DBAL\Connection');
    }

    /**
     * Get a Doctrine Schema Column instance.
     *
     * @param string $table
     * @param string $column
     *
     * @return \Doctrine\DBAL\Schema\Column
     */
    public function getDoctrineColumn($table, $column)
    {
        $schema = $this->getDoctrineSchemaManager();

        return $schema->listTableDetails($table)->getColumn($column);
    }

    /**
     * Get the Doctrine DBAL schema manager for the connection.
     *
     * @return \Doctrine\DBAL\Schema\AbstractSchemaManager
     */
    public function getDoctrineSchemaManager()
    {
        return $this->getDoctrineDriver()->getSchemaManager($this->getDoctrineConnection());
    }

    /**
     * Get the Doctrine DBAL database connection instance.
     *
     * @return \Doctrine\DBAL\Connection
     */
    public function getDoctrineConnection()
    {
        if (is_null($this->doctrineConnection)) {
            $driver = $this->getDoctrineDriver();

            $data = ['pdo' => $this->getPdo(), 'dbname' => $this->getConfig('database')];

            $this->doctrineConnection = new DoctrineConnection($data, $driver);
        }

        return $this->doctrineConnection;
    }

    /**
     * Get the current PDO connection.
     *
     * @return \PDO
     */
    public function getPdo()
    {
        if ($this->pdo instanceof Closure) {
            return $this->pdo = call_user_func($this->pdo);
        }

        return $this->pdo;
    }

    /**
     * Get the current PDO connection used for reading.
     *
     * @return \PDO
     */
    public function getReadPdo()
    {
        if ($this->transactions >= 1) {
            return $this->getPdo();
        }

        if ($this->readPdo instanceof Closure) {
            return $this->readPdo = call_user_func($this->readPdo);
        }

        return $this->readPdo ?: $this->getPdo();
    }

    /**
     * Set the PDO connection.
     *
     * @param \PDO|null $pdo
     *
     * @throws \RuntimeException
     *
     * @return $this
     */
    public function setPdo($pdo)
    {
        if ($this->transactions >= 1) {
            throw new RuntimeException("Can't swap PDO instance while within transaction.");
        }

        $this->pdo = $pdo;

        return $this;
    }

    /**
     * Set the PDO connection used for reading.
     *
     * @param \PDO|null $pdo
     *
     * @return $this
     */
    public function setReadPdo($pdo)
    {
        $this->readPdo = $pdo;

        return $this;
    }

    /**
     * Set the reconnect instance on the connection.
     *
     * @param callable $reconnector
     *
     * @return $this
     */
    public function setReconnector(callable $reconnector)
    {
        $this->reconnector = $reconnector;

        return $this;
    }

    /**
     * Get the database connection name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getConfig('name');
    }

    /**
     * Get an option from the configuration options.
     *
     * @param string $option
     *
     * @return mixed
     */
    public function getConfig($option)
    {
        return Arr::get($this->config, $option);
    }

    /**
     * Get the PDO driver name.
     *
     * @return string
     */
    public function getDriverName()
    {
        return $this->getConfig('driver');
    }

    /**
     * Get the query grammar used by the connection.
     *
     * @return \Illuminate\Database\Query\Grammars\Grammar
     */
    public function getQueryGrammar()
    {
        return $this->queryGrammar;
    }

    /**
     * Set the query grammar used by the connection.
     *
     * @param \Illuminate\Database\Query\Grammars\Grammar $grammar
     *
     * @return void
     */
    public function setQueryGrammar(Query\Grammars\Grammar $grammar)
    {
        $this->queryGrammar = $grammar;
    }

    /**
     * Get the schema grammar used by the connection.
     *
     * @return \Illuminate\Database\Schema\Grammars\Grammar
     */
    public function getSchemaGrammar()
    {
        return $this->schemaGrammar;
    }

    /**
     * Set the schema grammar used by the connection.
     *
     * @param \Illuminate\Database\Schema\Grammars\Grammar $grammar
     *
     * @return void
     */
    public function setSchemaGrammar(Schema\Grammars\Grammar $grammar)
    {
        $this->schemaGrammar = $grammar;
    }

    /**
     * Get the query post processor used by the connection.
     *
     * @return \Illuminate\Database\Query\Processors\Processor
     */
    public function getPostProcessor()
    {
        return $this->postProcessor;
    }

    /**
     * Set the query post processor used by the connection.
     *
     * @param \Illuminate\Database\Query\Processors\Processor $processor
     *
     * @return void
     */
    public function setPostProcessor(Processor $processor)
    {
        $this->postProcessor = $processor;
    }

    /**
     * Get the event dispatcher used by the connection.
     *
     * @return \Illuminate\Contracts\Events\Dispatcher
     */
    public function getEventDispatcher()
    {
        return $this->events;
    }

    /**
     * Set the event dispatcher instance on the connection.
     *
     * @param \Illuminate\Contracts\Events\Dispatcher $events
     *
     * @return void
     */
    public function setEventDispatcher(Dispatcher $events)
    {
        $this->events = $events;
    }

    /**
     * Determine if the connection in a "dry run".
     *
     * @return bool
     */
    public function pretending()
    {
        return $this->pretending === true;
    }

    /**
     * Get the default fetch mode for the connection.
     *
     * @return int
     */
    public function getFetchMode()
    {
        return $this->fetchMode;
    }

    /**
     * Get the fetch argument to be applied when selecting.
     *
     * @return mixed
     */
    public function getFetchArgument()
    {
        return $this->fetchArgument;
    }

    /**
     * Get custom constructor arguments for the PDO::FETCH_CLASS fetch mode.
     *
     * @return array
     */
    public function getFetchConstructorArgument()
    {
        return $this->fetchConstructorArgument;
    }

    /**
     * Set the default fetch mode for the connection, and optional arguments for the given fetch mode.
     *
     * @param int   $fetchMode
     * @param mixed $fetchArgument
     * @param array $fetchConstructorArgument
     *
     * @return int
     */
    public function setFetchMode($fetchMode, $fetchArgument = null, array $fetchConstructorArgument = [])
    {
        $this->fetchMode = $fetchMode;
        $this->fetchArgument = $fetchArgument;
        $this->fetchConstructorArgument = $fetchConstructorArgument;
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

    /**
     * Clear the query log.
     *
     * @return void
     */
    public function flushQueryLog()
    {
        $this->queryLog = [];
    }

    /**
     * Enable the query log on the connection.
     *
     * @return void
     */
    public function enableQueryLog()
    {
        $this->loggingQueries = true;
    }

    /**
     * Disable the query log on the connection.
     *
     * @return void
     */
    public function disableQueryLog()
    {
        $this->loggingQueries = false;
    }

    /**
     * Determine whether we're logging queries.
     *
     * @return bool
     */
    public function logging()
    {
        return $this->loggingQueries;
    }

    /**
     * Get the name of the connected database.
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->database;
    }

    /**
     * Set the name of the connected database.
     *
     * @param string $database
     *
     * @return string
     */
    public function setDatabaseName($database)
    {
        $this->database = $database;
    }

    /**
     * Get the table prefix for the connection.
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * Set the table prefix in use by the connection.
     *
     * @param string $prefix
     *
     * @return void
     */
    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;

        $this->getQueryGrammar()->setTablePrefix($prefix);
    }

    /**
     * Set the table prefix and return the grammar.
     *
     * @param \Illuminate\Database\Grammar $grammar
     *
     * @return \Illuminate\Database\Grammar
     */
    public function withTablePrefix(Grammar $grammar)
    {
        $grammar->setTablePrefix($this->tablePrefix);

        return $grammar;
    }
}
