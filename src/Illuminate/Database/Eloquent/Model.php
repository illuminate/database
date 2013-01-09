<?php namespace Illuminate\Database\Eloquent;

use Closure;
use DateTime;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\ConnectionResolverInterface as Resolver;

abstract class Model implements ArrayableInterface, JsonableInterface {

	/**
	 * The connection name for the model.
	 *
	 * @var string
	 */
	protected $connection;

	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The primary key for the model.
	 *
	 * @var string
	 */
	protected $primaryKey = 'id';

	/**
	 * The number of models to return for pagination.
	 *
	 * @var int
	 */
	protected $perPage = 15;

	/**
	 * Indicates if the IDs are auto-incrementing.
	 *
	 * @var bool
	 */
	public $incrementing = true;

	/**
	 * Indicates if the model should be timestamped.
	 *
	 * @var bool
	 */
	public $timestamps = true;

	/**
	 * The model's attributes.
	 *
	 * @var array
	 */
	protected $attributes = array();

	/**
	 * The model attribute's original state.
	 *
	 * @var array
	 */
	protected $original = array();

	/**
	 * The loaded relationships for the model.
	 *
	 * @var array
	 */
	protected $relations = array();

	/**
	 * The attributes that should be hidden for arrays.
	 *
	 * @var array
	 */
	protected $hidden = array();

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = array();

	/**
	 * The attribute that aren't mass assignable.
	 *
	 * @var array
	 */
	protected $guarded = array();

	/**
	 * The date fields for the model.
	 *
	 * @var array
	 */
	protected $dates = array();

	/**
	 * Indicates if the model exists.
	 *
	 * @var bool
	 */
	public $exists = false;

	/**
	 * The connection resolver instance.
	 *
	 * @var Illuminate\Database\ConnectionResolverInterface
	 */
	protected static $resolver;

	/**
	 * Create a new Eloquent model instance.
	 *
	 * @param  array  $attributes
	 * @return void
	 */
	public function __construct(array $attributes = array())
	{
		$this->fill($attributes);
	}

	/**
	 * Fill the model with an array of attributes.
	 *
	 * @param  array  $attributes
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function fill(array $attributes)
	{
		foreach ($attributes as $key => $value)
		{
			// The developers may choose to place some attributes in the "fillable"
			// array, which means only those attributes may be set through mass
			// assignment to the model, and all others will just be ignored.
			if ($this->isFillable($key))
			{
				$this->$key = $value;
			}
		}

		return $this;
	}

	/**
	 * Create a new instance of the given model.
	 *
	 * @param  array  $attributes
	 * @param  bool   $exists
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function newInstance($attributes = array(), $exists = false)
	{
		// This method just provides a convenient way for us to generate fresh model
		// instances of this current model. It is particularly useful during the
		// hydration of new objects via the Eloquent query builder instances.
		$model = new static((array) $attributes);

		$model->exists = $exists;

		return $model;
	}

	/**
	 * Create a new model instance that is existing.
	 *
	 * @param  array  $attributes
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function newExisting($attributes = array())
	{
		return $this->newInstance($attributes, true);
	}

	/**
	 * Save a new model and return the instance.
	 *
	 * @param  array  $attributes
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public static function create(array $attributes)
	{
		$model = new static($attributes);

		$model->save();

		return $model;
	}

	/**
	 * Begin querying the model on a given connection.
	 *
	 * @param  string  $connection
	 * @return Illuminate\Database\Eloquent\Builder
	 */
	public static function on($connection)
	{
		// First we will just create a fresh instance of this model, and then we can
		// set the connection on the model so that it is be used for the queries
		// we execute, as well as being set on each relationship we retrieve.
		$instance = new static;

		$instance->setConnection($connection);

		return $instance->newQuery();
	}

	/**
	 * Get all of the models from the database.
	 *
	 * @param  array  $columns
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public static function all($columns = array('*'))
	{
		$instance = new static;

		return $instance->newQuery()->get($columns);
	}

	/**
	 * Find a model by its primary key.
	 *
	 * @param  mixed  $id
	 * @param  array  $columns
	 * @return Illuminate\Database\Eloquent\Model|Collection
	 */
	public static function find($id, $columns = array('*'))
	{
		$instance = new static;

		if (is_array($id))
		{
			return $instance->newQuery()->whereIn($id)->get($columns);
		}

		return $instance->newQuery()->find($id, $columns);
	}

	/**
	 * Being querying a model with eager loading.
	 *
	 * @param  array  $relations
	 * @return Illuminate\Database\Eloquent\Builder
	 */
	public static function with($relations)
	{
		if (is_string($relations)) $relations = func_get_args();

		$instance = new static;

		return $instance->newQuery()->with($relations);
	}

	/**
	 * Define a one-to-one relationship.
	 *
	 * @param  string  $related
	 * @param  string  $foreignKey
	 * @return Illuminate\Database\Eloquent\Relation\HasOne
	 */
	public function hasOne($related, $foreignKey = null)
	{
		$foreignKey = $foreignKey ?: $this->getForeignKey();

		$instance = new $related;

		return new HasOne($instance->newQuery(), $this, $foreignKey);
	}

	/**
	 * Define a polymorphic one-to-one relationship.
	 *
	 * @param  string  $related
	 * @param  string  $name
	 * @param  string  $foreignKey
	 * @return Illuminate\Database\Eloquent\Relation\MorphOne
	 */
	public function morphOne($related, $name)
	{
		$instance = new $related;

		return new MorphOne($instance->newQuery(), $this, $name);
	}

	/**
	 * Define an inverse one-to-one or many relationship.
	 *
	 * @param  string  $related
	 * @param  string  $foreignKey
	 * @return Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function belongsTo($related, $foreignKey = null)
	{
		// If no foreign key was supplied, we can use a backtrace to guess the proper
		// foreign key name by using the name of the relationship function, which
		// when combined with an "_id" should conventionally match the columns.
		if (is_null($foreignKey))
		{
			list(, $caller) = debug_backtrace(false);

			$foreignKey = snake_case($caller['function']).'_id';
		}

		// Once we have the foreign key names, we'll just create a new Eloquent query
		// for the related models and returns the relationship instance which will
		// actually be responsible for retrieving and hydrating every relations.
		$instance = new $related;

		$query = $instance->newQuery();

		return new BelongsTo($query, $this, $foreignKey);
	}

	/**
	 * Define an polymorphic, inverse one-to-one or many relationship.
	 *
	 * @param  string  $name
	 * @return Illuminate\Database\Eloquent\Relations\BelongsTo
	 */
	public function morphTo($name = null)
	{
		// If no name is provided, we will use the backtrace to get the function name
		// since that is most likely the name of the polymorphic interface. We can
		// use that to get both the class and foreign key that will be utilized.
		if (is_null($name))
		{
			list(, $caller) = debug_backtrace(false);

			$name = snake_case($caller['function']);
		}

		return $this->belongsTo($this->{"{$name}_type"}, "{$name}_id");
	}

	/**
	 * Define a one-to-many relationship.
	 *
	 * @param  string  $related
	 * @param  string  $foreignKey
	 * @return Illuminate\Database\Eloquent\Relations\HasMany
	 */
	public function hasMany($related, $foreignKey = null)
	{
		$foreignKey = $foreignKey ?: $this->getForeignKey();

		$instance = new $related;

		return new HasMany($instance->newQuery(), $this, $foreignKey);
	}

	/**
	 * Define a polymorphic one-to-many relationship.
	 *
	 * @param  string  $related
	 * @param  string  $name
	 * @param  string  $foreignKey
	 * @return Illuminate\Database\Eloquent\Relation\MorphMany
	 */
	public function morphMany($related, $name)
	{
		$instance = new $related;

		return new MorphMany($instance->newQuery(), $this, $name);
	}

	/**
	 * Define a many-to-many relationship.
	 *
	 * @param  string  $related
	 * @param  string  $table
	 * @param  string  $foreignKey
	 * @param  string  $otherKey
	 * @return Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function belongsToMany($related, $table = null, $foreignKey = null, $otherKey = null)
	{
		// First, we'll need to determine the foreign key and "other key" for the
		// relationship. Once we have determined the keys we'll make the query
		// instances as well as the relationship instances we need for this.
		$foreignKey = $foreignKey ?: $this->getForeignKey();

		$instance = new $related;

		$otherKey = $otherKey ?: $instance->getForeignKey();

		// If no table name was provided, we can guess it by concatenating the two
		// models using underscores in alphabetical order. The two model names
		// are transformed to snake case from their default CamelCase also.
		if (is_null($table))
		{
			$table = $this->joiningTable($related);
		}

		// Now we're ready to create a new query builder for the related model and
		// the relationship instances for the relation. The relations will set
		// appropriate query constraint and entirely manages the hydrations.
		$query = $instance->newQuery();

		return new BelongsToMany($query, $this, $table, $foreignKey, $otherKey);
	}

	/**
	 * Get the joining table name for a many-to-many relation.
	 *
	 * @param  string  $related
	 * @return string
	 */
	public function joiningTable($related)
	{
		// The joining table name, by convention, is simply the snake cased models
		// sorted alphabetically and concatenated with an underscore, so we can
		// just sort the models and join them together to get the table name.
		$base = snake_case(class_basename($this));

		$related = snake_case(class_basename($related));

		$models = array($related, $base);

		// Now that we have the model names in an array we can just sort them and
		// use the implode function to join them together with an underscores,
		// which is typically used by convention within the database system.
		sort($models);

		return strtolower(implode('_', $models));
	}

	/**
	 * Delete the model from the database.
	 *
	 * @return void
	 */
	public function delete()
	{
		if ($this->exists)
		{
			$key = $this->getKeyName();

			return $this->newQuery()->where($key, $this->getKey())->delete();
		}
	}

	/**
	 * Save the model to the database.
	 *
	 * @return bool
	 */
	public function save()
	{
		$keyName = $this->getKeyName();

		// First we need to create a fresh query instance and touch the creation and
		// update timestamp on the model which are maintained by us for developer
		// convenience. Then we will just continue saving the model instances.
		$query = $this->newQuery();

		if ($this->timestamps)
		{
			$this->updateTimestamps();
		}

		// If the model already exists in the database we can just update our record
		// that is already in this database using the current IDs in this "where"
		// clause to only update this model. Otherwise, we'll just insert them.
		if ($this->exists)
		{
			$this->setKeysForSaveQuery($query);

			$query->update($this->attributes);
		}

		// If the model is brand new, we'll insert it into our database and set the
		// ID attribute on the model to the value of the newly inserted row's ID
		// which is typically an auto-increment value managed by the database.
		else
		{
			if ($this->incrementing)
			{
				$this->$keyName = $query->insertGetId($this->attributes);
			}
			else
			{
				$query->insert($this->attributes);
			}
		}

		return $this->exists = true;
	}

	/**
	 * Set the keys for a save update query.
	 *
	 * @param  Illuminate\Database\Eloquent\Builder
	 * @return void
	 */
	protected function setKeysForSaveQuery($query)
	{
		$query->where($this->getKeyName(), '=', $this->getKey());
	}

	/**
	 * Update the creation and update timestamps.
	 *
	 * @return void
	 */
	protected function updateTimestamps()
	{
		$this->updated_at = $this->freshTimestamp();

		if ( ! $this->exists)
		{
			$this->created_at = $this->updated_at;
		}
	}

	/**
	 * Get a fresh timestamp for the model.
	 *
	 * @return mixed
	 */
	public function freshTimestamp()
	{
		return new DateTime;
	}

	/**
	 * Get a new query builder for the model's table.
	 *
	 * @return Illuminate\Database\Eloquent\Builder
	 */
	public function newQuery()
	{
		$builder = new Builder($this->newBaseQueryBuilder());

		// Once we have the query builders, we will set the model instances so the
		// builder can easily access any information it may need from the model
		// while it is constructing and executing various queries against it.
		$builder->setModel($this);

		return $builder;
	}

	/**
	 * Get a new query builder instance for the connection.
	 *
	 * @return Illuminate\Database\Query\Builder
	 */
	protected function newBaseQueryBuilder()
	{
		$conn = $this->getConnection();

		$grammar = $conn->getQueryGrammar();

		return new QueryBuilder($conn, $grammar, $conn->getPostProcessor());
	}

	/**
	 * Create a new Eloquent Collection instance.
	 *
	 * @param  array  $models
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public function newCollection(array $models = array())
	{
		return new Collection($models);
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
	 * Get the value of the model's primary key.
	 *
	 * @return mixed
	 */
	public function getKey()
	{
		return $this->getAttribute($this->getKeyName());
	}

	/**
	 * Get the primary key for the model.
	 *
	 * @return string
	 */
	public function getKeyName()
	{
		return $this->primaryKey;
	}

	/**
	 * Determine if the model uses timestamps.
	 *
	 * @return bool
	 */
	public function usesTimestamps()
	{
		return $this->timestamps;
	}

	/**
	 * Get the number of models to return per page.
	 *
	 * @return int
	 */
	public function getPerPage()
	{
		return $this->perPage;
	}

	/**
	 * Set the number of models ot return per page.
	 *
	 * @param  int   $perPage
	 * @return void
	 */
	public function setPerPage($perPage)
	{
		$this->perPage = $perPage;
	}

	/**
	 * Get the default foreign key name for the model.
	 *
	 * @return string
	 */
	public function getForeignKey()
	{
		return snake_case(class_basename($this)).'_id';
	}

	/**
	 * Get the hidden attributes for the model.
	 *
	 * @return array
	 */
	public function getHidden()
	{
		return $this->hidden;
	}

	/**
	 * Set the hidden attributes for the model.
	 *
	 * @param  array  $hidden
	 * @return void
	 */
	public function setHidden(array $hidden)
	{
		$this->hidden = $hidden;
	}

	/**
	 * Get the fillable attributes for the model.
	 *
	 * @return array
	 */
	public function getFillable()
	{
		return $this->fillable;
	}

	/**
	 * Set the fillable attributes for the model.
	 *
	 * @param  array  $fillable
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function fillable(array $fillable)
	{
		$this->fillable = $fillable;

		return $this;
	}

	/**
	 * Set the guarded attributes for the model.
	 *
	 * @param  array  $guarded
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function guard(array $guarded)
	{
		$this->guarded = $guarded;

		return $this;
	}

	/**
	 * Determine if the given attribute may be mass assigned.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function isFillable($key)
	{
		if (in_array($key, $this->fillable)) return true;

		if (in_array($key, $this->guarded) or $this->guarded == array('*'))
		{
			return false;
		}

		return empty($this->fillable);
	}

	/**
	 * Get the value indicating whether the IDs are incrementing.
	 *
	 * @return bool
	 */
	public function getIncrementing()
	{
		return $this->incrementing;
	}

	/**
	 * Set whether IDs are incrementing.
	 *
	 * @param  bool  $value
	 * @return void
	 */
	public function setIncrementing($value)
	{
		$this->incrementing = $value;
	}

	/**
	 * Convert the model instance to JSON.
	 *
	 * @param  int  $options
	 * @return string
	 */
	public function toJson($options = 0)
	{
		return json_encode($this->toArray(), $options);
	}

	/**
	 * Convert the model instance to an array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		$attributes = array_diff_key($this->attributes, array_flip($this->hidden));

		return array_merge($attributes, $this->relationsToArray());
	}

	/**
	 * Get the model's relationships in array form.
	 *
	 * @return array
	 */
	public function relationsToArray()
	{
		$attributes = array();

		foreach ($this->relations as $key => $value)
		{
			// If the values implements the Arrayable interface we can just call this
			// toArray method on the instances which will convert both models and
			// collections to their proper array form and we'll set the values.
			if ($value instanceof ArrayableInterface)
			{
				$attributes[$key] = $value->toArray();
			}

			// If the value is null, we'll still go ahead and set it in this list of
			// attributes since null is used to represent empty relationships if
			// if it a has one or belongs to type relationships on the models.
			elseif (is_null($value))
			{
				$attributes[$key] = $value;
			}
		}

		return $attributes;
	}

	/**
	 * Get an attribute from the model.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function getAttribute($key)
	{
		$inAttributes = array_key_exists($key, $this->attributes);

		// If the key references an attribute, we can just go ahead and return the
		// plain attribute value from the model. This allows every attribute to
		// be dynamically accessed through the _get method without accessors.
		if ($inAttributes or $this->hasGetMutator($key))
		{
			return $this->getPlainAttribute($key);
		}

		// If the key already exists in the relationships array, it just means the
		// relationship has already been loaded, so we'll just return it out of
		// here because there is no need to query within the relations twice.
		if (array_key_exists($key, $this->relations))
		{
			return $this->relations[$key];
		}

		// If the "attribute" exists as a method on the model, we will just assume
		// it is a relationship and will load and return results from the query
		// and hydrate the relationship's value on the "relationships" array.
		if (method_exists($this, $key))
		{
			$relations = $this->$key()->getResults();

			return $this->relations[$key] = $relations;
		}
	}

	/**
	 * Get a plain attribute (not a relationship).
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	protected function getPlainAttribute($key)
	{
		$value = $this->getAttributeFromArray($key);

		// If the attribute has a get mutator, we will call that then return what
		// it returns as the value, which is useful for transforming values on
		// retrieval from the model to a form that is more useful for usage.
		if ($this->hasGetMutator($key))
		{
			return $this->{'get'.camel_case($key)}($value);
		}

		// If the attribute is listed as a date, we will convert it to a DateTime
		// instance on retrieval, which makes it quite convenient to work with
		// date fields without having to create a mutator for each property.
		elseif (in_array($key, $this->dates))
		{
			if ($value) return $this->asDateTime($value);
		}

		return $value;
	}

	/**
	 * Get an attribute from the $attributes array.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	protected function getAttributeFromArray($key)
	{
		if (array_key_exists($key, $this->attributes))
		{
			return $this->attributes[$key];
		}
	}

	/**
	 * Determine if a get mutator exists for an attribute.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function hasGetMutator($key)
	{
		return method_exists($this, 'get'.camel_case($key));
	}

	/**
	 * Set a given attribute on the model.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function setAttribute($key, $value)
	{
		// First we will check for the presence of a mutator for the set operation
		// which simply lets the developers tweak the attribute as it is set on
		// the model, such as "json_encoding" an listing of data for storage.
		if ($this->hasSetMutator($key))
		{
			$method = 'set'.camel_case($key);

			return $this->{$method}($value);
		}

		// If an attribute is listed as a "date", we'll convert it from a DateTime
		// instance into a form proper for storage on the database tables using
		// the connection grammar's date format. We will auto set the values.
		elseif (in_array($key, $this->dates))
		{
			if ($value)
			{
				$this->attributes[$key] = $this->fromDateTime($value);
			}
		}

		$this->attributes[$key] = $value;
	}

	/**
	 * Determine if a set mutator exists for an attribute.
	 *
	 * @param  string  $key
	 * @return bool
	 */
	public function hasSetMutator($key)
	{
		return method_exists($this, 'set'.camel_case($key));
	}

	/**
	 * Convert a DateTime to a storable string.
	 *
	 * @param  DateTime  $value
	 * @return string
	 */
	protected function fromDateTime(DateTime $value)
	{
		return $value->format($this->getDateFormat());
	}

	/**
	 * Return a timestamp as DateTime object.
	 *
	 * @param  mixed  $value
	 * @return DateTime
	 */
	protected function asDateTime($value)
	{
		if ( ! $value instanceof DateTime)
		{
			$format = $this->getDateFormat();

			return DateTime::createFromFormat($format, $value);
		}

		return $value;
	}

	/**
	 * Get the format for databsae stored dates.
	 *
	 * @return string
	 */
	protected function getDateFormat()
	{
		return $this->getConnection()->getQueryGrammar()->getDateFormat();
	}

	/**
	 * Get all of the current attributes on the model.
	 *
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * Set the array of model attributes. No checking is done.
	 *
	 * @param  array  $attributes
	 * @param  bool   $sync
	 * @return void
	 */
	public function setRawAttributes(array $attributes, $sync = false)
	{
		$this->attributes = $attributes;

		if ($sync) $this->syncOriginal();
	}

	/**
	 * Get the model's original attribute values.
	 *
	 * @param  string|null  $key
	 * @param  mixed  $default
	 * @return array
	 */
	public function getOriginal($key = null, $default = null)
	{
		return array_get($this->original, $key, $default);
	}

	/**
	 * Sync the original attributes with the current.
	 *
	 * @return void
	 */
	public function syncOriginal()
	{
		$this->original = $this->attributes;
	}

	/**
	 * Get a specified relationship.
	 *
	 * @param  string  $relation
	 * @return mixed
	 */
	public function getRelation($relation)
	{
		return $this->relations[$relation];
	}

	/**
	 * Set the specific relationship in the model.
	 *
	 * @param  string  $relation
	 * @param  mixed   $value
	 * @return void
	 */
	public function setRelation($relation, $value)
	{
		$this->relations[$relation] = $value;
	}

	/**
	 * Get the database connection for the model.
	 *
	 * @return Illuminate\Database\Connection
	 */
	public function getConnection()
	{
		return static::resolveConnection($this->connection);
	}

	/**
	 * Get the current connection name for the model.
	 *
	 * @return string
	 */
	public function getConnectionName()
	{
		return $this->connection;
	}

	/**
	 * Set the connection associated with the model.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setConnection($name)
	{
		$this->connection = $name;
	}

	/**
	 * Resolve a connection instance by name.
	 *
	 * @param  string  $connection
	 * @return Illuminate\Database\Connection
	 */
	public static function resolveConnection($connection)
	{
		return static::$resolver->connection($connection);
	}

	/**
	 * Get the connection resolver instance.
	 *
	 * @return Illuminate\Database\ConnectionResolverInterface
	 */
	public static function getConnectionResolver()
	{
		return static::$resolver;
	}

	/**
	 * Set the connection resolver instance.
	 *
	 * @param  Illuminate\Database\ConnectionResolverInterface  $resolver
	 * @return void
	 */
	public static function setConnectionResolver(Resolver $resolver)
	{
		static::$resolver = $resolver;
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

	/**
	 * Handle dynamic method calls into the method.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		$query = $this->newQuery();

		return call_user_func_array(array($query, $method), $parameters);
	}

	/**
	 * Handle dynamic static method calls into the method.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public static function __callStatic($method, $parameters)
	{
		$instance = new static;

		return call_user_func_array(array($instance, $method), $parameters);
	}

	/**
	 * Conver the model to its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->toJson();
	}

}
