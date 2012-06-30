<?php namespace Illuminate\Database\Eloquent;

abstract class Model {

	/**
	 * The table associated with the model.
	 *
	 * @return string
	 */
	protected $table;

	/**
	 * The model's attributes.
	 *
	 * @var array
	 */
	protected $attributes;

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
	 * Dynamically retrieve attributes on the model.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->attributes[$key];
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
		$this->attributes[$key] = $value;
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