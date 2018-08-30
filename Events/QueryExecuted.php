<?php

namespace Illuminate\Database\Events;

class QueryExecuted
{
    /**
     * The SQL query that was executed.
     *
     * @var string
     */
    public $sql;

    /**
     * The array of query bindings.
     *
     * @var array
     */
    public $bindings;

    /**
     * The number of milliseconds it took to execute the query.
     *
     * @var float
     */
    public $time;
    
    /**
     * The number of rows query execution has affected.
     *
     * @var int
     */
    public $rows;

    /**
     * The database connection instance.
     *
     * @var \Illuminate\Database\Connection
     */
    public $connection;

    /**
     * The database connection name.
     *
     * @var string
     */
    public $connectionName;

    /**
     * Create a new event instance.
     *
     * @param  string  $sql
     * @param  array  $bindings
     * @param  float|null  $time
     * @param  int  $rows
     * @param  \Illuminate\Database\Connection  $connection
     * @return void
     */
    public function __construct($sql, $bindings, $time, $rows, $connection)
    {
        $this->sql = $sql;
        $this->time = $time;
        $this->rows = $rows;
        $this->bindings = $bindings;
        $this->connection = $connection;
        $this->connectionName = $connection->getName();
    }
}
