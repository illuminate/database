<?php namespace Illuminate\Database\Eloquent;

use Illuminate\Database\Connection;

abstract class Model {

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The connection for the model.
	 *
	 * @var string
	 */
	protected $connection;

	/**
	 * The model's attributes.
	 *
	 * @var array
	 */
	protected $attributes;

	/**
	 * Indicates if the model exists.
	 *
	 * @var bool
	 */
	public $exists = false;

	/**
	 * The connections registered with Eloquent.
	 *
	 * @var array
	 */
	protected static $connections = array();

	/**
	 * The default connection name.
	 *
	 * @var string
	 */
	protected static $defaultConnection;

	/**
	 * Create a new Eloquent model instance.
	 *
	 * @param  array  $attributes
	 * @return void
	 */
	public function __construct(array $attributes = array())
	{
		$this->attributes = $attributes;
	}

	/**
	 * Save a new model and return the instance.
	 *
	 * @param  array  $attributes
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public static function create(array $attributes)
	{
		//
	}

	/**
	 * Find a model by its primary key.
	 *
	 * @param  mixed  $id
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public static function find($id)
	{
		//
	}

	/**
	 * Being querying a model with eager loading.
	 *
	 * @param  array  $relations
	 * @return ?
	 */
	public static function with($relations)
	{
		if (is_string($relations)) $relations = func_get_args();

		//
	}

	/**
	 * Define a one-to-one relationship.
	 *
	 * @return ?
	 */
	public function hasOne()
	{
		//
	}

	/**
	 * Define an inverse one-to-one or many relationship.
	 *
	 * @return ?
	 */
	public function belongsTo()
	{
		//
	}

	/**
	 * Define a one-to-many relationship.
	 *
	 * @return ?
	 */
	public function hasMany()
	{
		//
	}

	/**
	 * Define a many-to-many relationship.
	 *
	 * @return ?
	 */
	public function belongsToMany()
	{
		//
	}

	/**
	 * Save the model to the database.
	 *
	 * @return bool
	 */
	public function save()
	{
		//
	}

	/**
	 * Get a new query builder for the model's table.
	 *
	 * @return Illuminate\Database\Query\Builder
	 */
	public function newQuery()
	{
		return $this->getConnection()->table($this->getTable());
	}

	/**
	 * Get the table associated with the model.
	 *
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}

	/**
	 * Set the table associated with the model.
	 *
	 * @param  string  $table
	 * @return void
	 */
	public function setTable($table)
	{
		$this->table = $table;
	}

	/**
	 * Get the database connection for the model.
	 *
	 * @return Illuminate\Database\Connection
	 */
	public function getConnection()
	{
		return $this->connection ?: static::getDefaultConnection();
	}

	/**
	 * Set the connection associated with the model.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setConnection($name)
	{
		$this->connection = static::$connections[$name];
	}

	/**
	 * Register a connection with Eloquent.
	 *
	 * @param  string  $name
	 * @param  Illuminate\Database\Connection  $connection
	 * @return void
	 */
	public static function addConnection($name, Connection $connection)
	{
		if (count(static::$connections) == 0)
		{
			static::$defaultConnection = $name;
		}

		static::$connections[$name] = $connection;
	}

	/**
	 * Get the default connection instance.
	 *
	 * @return Illuminate\Database\Connection
	 */
	public static function getDefaultConnection()
	{
		return static::$connections[static::$defaultConnection];
	}

	/**
	 * Set the default connection name.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public static function setDefaultConnectionName($name)
	{
		static::$defaultConnection = $name;
	}

	/**
	 * Get an attribute from the model.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		return $this->attributes[$key];
	}

	/**
	 * Dynamically retrieve attributes on the model.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->getAttribute($key);
	}

	/**
	 * Dynamically set attributes on the model.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->setAttribute($key, $value);
	}

	/**
	 * Determine if an attribute exists on the model.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function __isset($key)
	{
		return isset($this->attributes[$key]);
	}

	/**
	 * Unset an attribute on the model.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function __unset($key)
	{
		unset($this->attributes[$key]);
	}

}