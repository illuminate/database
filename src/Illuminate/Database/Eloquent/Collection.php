<?php namespace Illuminate\Database\Eloquent;

use Closure;
use Countable;
use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Illuminate\Support\JsonableInterface;

class Collection implements ArrayAccess, ArrayableInterface, Countable, IteratorAggregate, JsonableInterface {

	/**
	 * The builder for our queries.
	 *
	 * @var Illuminate\Database\Eloquent\Builder
	 */
	protected $builder;

	/**
	 * The items contained in the collection.
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * Create a new Eloquent result collection.
	 *
	 * @param  array  $items
	 * @return void
	 */
	public function __construct(array $items = array())
	{
		$this->items = $items;
	}

	/**
	 * Set a builder instance that is responsible for querying the model.
	 *
	 * @param  Illuminate\Database\Eloquent\Builder  $builder
	 * @return void
	 */
	public function setBuilder(Builder $builder)
	{
		$this->builder = $builder;
	}

	/**
	 * Add an item to the collection.
	 *
	 * @param  mixed  $item
	 * @return void
	 */
	public function add($item)
	{
		$this->items[] = $item;
	}

	/**
	 * Eagerly load a relation for all models in the collection.
	 * 
	 * @param  string        $name
	 * @param  Closure|null  $constraints
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public function load($name, Closure $constraints = null)
	{
		$models = $this->items;

		// First we will "back up" the existing where conditions on the query so we can
		// add our eager constraints. Then we will merge the wheres that were on the
		// query back to it in order that any where conditions might be specified.
		$relation = $this->getRelation($name);

		list($wheres, $bindings) = $relation->getAndResetWheres();

		$relation->addEagerConstraints($models);

		// We allow the developers to specify constraints on eager loads and we'll just
		// call the constraints Closure, passing along the query so they will simply
		// do all they need to the queries, and even may specify non-where things.
		$relation->mergeWheres($wheres, $bindings);

		if ( ! is_null($constraints))
		{
			call_user_func($constraints, $relation);
		}

		$models = $relation->initRelation($models, $name);

		// Once we have the results, we just match those back up to their parent models
		// using the relationship instance. Then we just return the finished arrays
		// of models which have been eagerly hydrated and are readied for return.
		$results = $relation->get();

		$this->items = $relation->match($models, $results, $name);

		return $this;
	}

	/**
	 * Get the relation instance for the given relation name.
	 *
	 * @param  string  $relation
	 * @return Illuminate\Database\Eloquent\Relations\Relation
	 */
	public function getRelation($relation)
	{
		$query = $this->builder->getModel()->$relation();

		// If there are nosted relationships set on the query, we will put those onto
		// the query instances so that they can be handled after this relationship
		// is loaded. In this way they will all trickle down as they are loaded.
		$nested = $this->builder->nestedRelations($relation);

		if (count($nested) > 0)
		{
			$query->getQuery()->with($nested);
		}

		return $query;
	}

	/**
	 * Get the first item from the collection.
	 *
	 * @return mixed|null
	 */
	public function first()
	{
		return count($this->items) > 0 ? reset($this->items) : null;
	}

	/**
	 * Get the collection of items as a plain array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		return array_map(function($value)
		{
			return $value->toArray();

		}, $this->items);
	}

	/**
	 * Get the collection of items as JSON.
	 *
	 * @return string
	 */
	public function toJson()
	{
		return json_encode($this->toArray());
	}

	/**
	 * Get all of the items in the collection.
	 *
	 * @return array
	 */
	public function all()
	{
		return $this->items;
	}

	/**
	 * Determine if the collection is empty or not.
	 *
	 * @return bool
	 */
	public function isEmpty()
	{
		return empty($this->items);
	}


	/**
	 * Get an iterator for the items.
	 *
	 * @return ArrayIterator
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->items);
	}

	/**
	 * Count the number of items in the collection.
	 *
	 * @return int
	 */
	public function count()
	{
		return count($this->items);
	}

	/**
	 * Determine if an item exists at an offset.
	 *
	 * @param  mixed  $key
	 * @return bool
	 */
	public function offsetExists($key)
	{
		return array_key_exists($key, $this->items);
	}

	/**
	 * Get an item at a given offset.
	 *
	 * @param  mixed  $key
	 * @return mixed
	 */
	public function offsetGet($key)
	{
		return $this->items[$key];
	}

	/**
	 * Set the item at a given offset.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($key, $value)
	{
		$this->items[$key] = $value;
	}

	/**
	 * Unset the item at a given offset.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function offsetUnset($key)
	{
		unset($this->items[$key]);
	}

	/**
	 * Convert the collection to its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->toJson();
	}

}
