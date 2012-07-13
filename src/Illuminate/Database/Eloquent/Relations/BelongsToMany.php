<?php namespace Illuminate\Database\Eloquent\Relations;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BelongsToMany extends Relation {

	/**
	 * The intermediate table for the relation.
	 *
	 * @var string
	 */
	protected $table;

	/**
	 * The foreign key of the parent model.
	 *
	 * @var string
	 */
	protected $foreignKey;

	/**
	 * The associated key of the relation.
	 *
	 * @var string
	 */
	protected $otherKey;

	/**
	 * The pivot table columns to retrieve.
	 *
	 * @var array
	 */
	protected $pivotColumns = array();

	/**
	 * Create a new has many relationship instance.
	 *
	 * @param  Illuminate\Database\Eloquent\Builder  $query
	 * @param  Illuminate\Database\Eloquent\Model  $parent
	 * @param  string  $table
	 * @param  string  $foreignKey
	 * @param  string  $otherKey
	 * @return void
	 */
	public function __construct(Builder $query, Model $parent, $table, $foreignKey, $otherKey)
	{
		$this->table = $table;
		$this->otherKey = $otherKey;
		$this->foreignKey = $foreignKey;
		parent::__construct($query, $parent);
	}

	/**
	 * Attach a model to the parent.
	 *
	 * @param  mixed  $id
	 * @param  array  $attributes
	 * @return void
	 */
	public function attach($id, array $attributes = array())
	{
		$foreign = $this->foreignKey;

		// When attaching models in a many to many relationship, we need to set the
		// keys on the pivot table, including both the foreign key and the other
		// associated keys before saving so it will automatically link models.
		$query = $this->query->newQuery()->from($this->table);

		$attributes[$foreign] = $this->parent->getKey();

		$attributes[$this->otherKey] = $id;

		// Since all Eloquent models are timestamped, we will set the creation and
		// update timestamps on the pivot record so they will be initialized on
		// the record like all of the other pivot table records on the table.
		$attributes['created_at'] = new DateTime;

		$attributes['updated_at'] = new DateTime;

		return $query->insert($attributes);
	}

	/**
	 * Detach models from the relationship.
	 *
	 * @param  int|array  $ids
	 * @return int
	 */
	public function detach($ids = array())
	{
		$query = $this->query->newQuery()->from($this->table);

		$query->where($this->foreignKey, '=', $this->parent->getKey());

		// If associated IDs were passed to the method we will onyl delete those
		// associations, otherwise all of the association ties will be broken.
		// We'll return the numbers of affected rows when we do the deletes.
		$ids = (array) $ids;

		if (count($ids) > 0)
		{
			$query->whereIn($this->otherKey, $ids);
		}

		return $query->delete();
	}

	/**
	 * Set the base constraints on the relation query.
	 *
	 * @return void
	 */
	public function addConstraints()
	{
		$this->setSelect()->setJoin()->setWhere();
	}

	/**
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	public function getResults()
	{
		return $this->get();
	}

	/**
	 * Execute the query as a "select" statement.
	 *
	 * @param  array  $columns
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public function get($columns = array('*'))
	{
		$models = $this->query->getModels($columns);

		$this->hydratePivotRelation($models);

		// If we actually found models we will also eager load any relationships that
		// have been specified as needing to be eager loaded. This will solve the
		// n+1 query problem for the developers and also increase performance.
		if (count($models) > 0)
		{
			$models = $this->query->eagerLoadRelations($models);
		}

		return new Collection($models);
	}

	/**
	 * Set the select clause for the relation query.
	 *
	 * @return Illuminate\Database\Eloquent\Relation\BelongsToMany
	 */
	protected function setSelect()
	{
		$columns = array($this->query->getModel()->getTable().'.*');

		$this->query->select(array_merge($columns, $this->getPivotColumns()));

		return $this;
	}

	/**
	 * Get the pivot columns for the relation.
	 *
	 * @return array
	 */
	protected function getPivotColumns()
	{
		$defaults = array('id', 'created_at', 'updated_at');

		$pivot = array_merge($defaults, $this->pivotColumns);

		// We need to alias all of the pivot columns with the "pivot_" prefix so we
		// can easily extract them out of the models and put them into the pivot
		// relationships when they are retrieved and hydrated into the models.
		foreach ($pivot as $column)
		{
			$pivot[] = $this->table.'.'.$column.' as pivot_'.$column;
		}

		return $pivot;
	}

	/**
	 * Set the join clause for the relation query.
	 *
	 * @return Illuminate\Database\Eloquent\Relation\BelongsToMany
	 */
	protected function setJoin()
	{
		// We need to join to the intermediate table on the related model's primary
		// key column with the intermediate tables foreign key for the related
		// model instance. Then we can set the where for the parent model.
		$baseTable = $this->query->getModel()->getTable();

		$key = $baseTable.'.'.$this->query->getModel()->getKeyName();

		$this->query->join($this->table, $key, '=', $this->getOtherKey());

		return $this;
	}

	/**
	 * Set the where clause for the relation query.
	 *
	 * @return Illuminate\Database\Eloquent\Relation\BelongsToMany
	 */
	protected function setWhere()
	{
		$foreign = $this->getForeignKey();

		$this->query->where($foreign, '=', $this->parent->getKey());

		return $this;
	}

	/**
	 * Set the constraints for an eager load of the relation.
	 *
	 * @param  array  $models
	 * @return void
	 */
	public function addEagerConstraints(array $models)
	{
		$this->query->whereIn($this->getForeignKey(), $this->getKeys($models));
	}

	/**
	 * Initialize the relation on a set of models.
	 *
	 * @param  array   $models
	 * @param  string  $relation
	 * @return void
	 */
	public function initRelation(array $models, $relation)
	{
		foreach ($models as $model)
		{
			$model->setRelation($relation, new Collection);
		}

		return $models;
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param  array   $models
	 * @param  Illuminate\Database\Eloquent\Collection  $results
	 * @param  string  $relation
	 * @return array
	 */
	public function match(array $models, Collection $results, $relation)
	{
		// First we'll build a dictionary of child models keyed by the foreign key
		// of the relation so that we can easily and quickly match them to the
		// parents without having a possibly slow inner loops for each one.
		$foreign = $this->foreignKey;

		$dictionary = array();

		foreach ($results as $result)
		{
			$dictionary[$result->pivot->$foreign][] = $result;
		}

		// Once we have a nice dictionary of child objects we can easily match the
		// children back to their parents using the dictionary and the keys on
		// the parent models. Then we will return the hydrated models back.
		foreach ($models as $model)
		{;
			if (isset($dictionary[$key = $model->getKey()]))
			{
				$collection = new Collection($dictionary[$key]);

				$model->setRelation($relation, $collection);
			}
		}

		// Now that we should have the children models totally matched up to their
		// parents, we can just return the array of models. Eager loading them
		// helps the developer avoid tons of queries (n + 1 query problems).
		return $models;
	}

	/**
	 * Hydrate the pivot table relationship on the models.
	 *
	 * @param  array  $models
	 * @return void
	 */
	protected function hydratePivotRelation(array $models)
	{
		// To hydrate the pivot relationship, we will just gather the pivot attributes
		// and create a new Pivot model, which is basically a dynamic model that we
		// will set the attributes, table, and connections on so it they be used.
		foreach ($models as $model)
		{
			$values = $this->cleanPivotAttributes($model);

			$pivot = $this->newExistingPivot($values);

			$model->setRelation('pivot', $pivot);
		}
	}

	/**
	 * Get the pivot attributes from a model.
	 *
	 * @param  Illuminate\Database\Eloquent\Model  $model
	 * @return array
	 */
	protected function cleanPivotAttributes(Model $model)
	{
		$values = array();

		// To get the pivots attributes we will just take any of the attributes that
		// begin with "pivot_" and add those to our arrays, as well as unsetting
		// them from the parents models since they exist in a different table.
		foreach ($model->getAttributes() as $key => $value)
		{
			if (strpos($key, 'pivot_') === 0)
			{
				$values[substr($key, 6)] = $value;

				unset($model->$key);
			}
		}

		return $values;
	}

	/**
	 * Create a new pivot model instance.
	 *
	 * @param  array  $attributes
	 * @param  bool   $exists
	 * @return Illuminate\Database\Eloquent\Relation\Pivot
	 */
	protected function newPivot(array $attributes = array(), $exists = false)
	{
		$connection = $this->parent->getConnectionName();

		return new Pivot($attributes, $this->table, $connection, $exists);
	}

	/**
	 * Create a new existing pivot model instance.
	 *
	 * @param  array  $attributes
	 * @return Illuminate\Database\Eloquent\Relations\Pivot
	 */
	protected function newExistingPivot(array $attributes = array())
	{
		return $this->newPivot($attributes, true);
	}

	/**
	 * Set the columns on the pivot table to retrieve.
	 *
	 * @param  array  $columns
	 * @return Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function withPivot($columns)
	{
		$this->pivotColumns = $columns;

		return $this->setSelect();
	}

	/**
	 * Get the fully qualified foreign key for the relation.
	 *
	 * @return string
	 */
	public function getForeignKey()
	{
		return $this->table.'.'.$this->foreignKey;
	}

	/**
	 * Get the fully qualified "other key" for the relation.
	 *
	 * @return string
	 */
	public function getOtherKey()
	{
		return $this->table.'.'.$this->otherKey;
	}

	/**
	 * Get both of the relation keys in an array.
	 *
	 * @return array
	 */
	protected function getBothKeys()
	{
		return array_map(function($value)
		{
			return $value.' as pivot_'.$value;

		}, array($this->foreignKey, $this->otherKey));
	}

	/**
	 * Get the intermediate table for the relationship.
	 *
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}

}