<?php namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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
		parent::__construct($query, $parent);

		$this->table = $table;
		$this->otherKey = $otherKey;
		$this->foreignKey = $foreignKey;
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
		return $this->query->get();
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

		$this->hydratePivotTable($models);

		// If we actually found models we will also eager load any relationships
		// that have been specified as needing to be eager loaded. This will
		// solve the n + 1 query problem for the developers conveniently.
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

		$this->query->select($columns);

		return $this;
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
		$this->query->whereIn($this->getForeignKey(), $this->getKeys($results));
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
			$model->setRelation($relation, array());
		}

		return $models;
	}

	/**
	 * Match the eagerly loaded results to their parents.
	 *
	 * @param  array   $models
	 * @param  array   $results
	 * @param  string  $relation
	 * @return array
	 */
	public function match(array $models, array $results, $relation)
	{
		//
	}

	/**
	 * Hydrate the pivot table relationship on the models.
	 *
	 * @param  array  $models
	 * @return void
	 */
	protected function hydratePivotRelation(array $models)
	{
		$conn = $this->parent->getConnectionName();

		// To hydrate the pivot relationship, we will just gather the pivot attributes
		// and create a new Pivot model, which is basically a dynamic model that we
		// will set the attributes, table, and connections on so it they be used.
		foreach ($models as $model)
		{
			$values = $this->cleanPivotAttributes($model);

			$pivot = new Pivot($values, $this->table, $conn);

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

				unset($model[$key]);
			}
		}

		return $values;
	}

	/**
	 * Get the fully qualified foreign key for the relation.
	 *
	 * @return string
	 */
	protected function getForeignKey()
	{
		return $this->table.'.'.$this->foreignKey;
	}

	/**
	 * Get the fully qualified "other key" for the relation.
	 *
	 * @return string
	 */
	protected function getOtherKey()
	{
		return $this->table.'.'.$this->otherKey;
	}

}