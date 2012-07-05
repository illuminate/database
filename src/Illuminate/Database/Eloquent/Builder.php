<?php namespace Illuminate\Database\Eloquent;

use Closure;
use Illuminate\Database\Query\Builder as BaseBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Builder extends BaseBuilder {

	/**
	 * The model being queried.
	 *
	 * @var Illuminate\Database\Eloquent\Model
	 */
	protected $model;

	/**
	 * The relationships that should be eager loaded.
	 *
	 * @var array
	 */
	protected $eagerLoad = array();

	/**
	 * Find a model by its primary key.
	 *
	 * @param  mixed  $id
	 * @param  array  $columns
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function find($id, $columns = array('*'))
	{
		$this->where($this->model->getKeyName(), '=', $id);

		return $this->first($columns);
	}

	/**
	 * Execute the query and get the first result.
	 *
	 * @param  array   $columns
	 * @return array
	 */
	public function first($columns = array('*'))
	{
		return $this->get($columns)->first();
	}

	/**
	 * Execute the query as a "select" statement.
	 *
	 * @param  array  $columns
	 * @return Illuminate\Database\Eloquent\Collection
	 */
	public function get($columns = array('*'))
	{
		// First we will simply get the raw reuslts from the query builder which we
		// can use to popular an array of Eloquent models. We will pass columns
		// that should be selected too, which is typically just everything.
		$results = parent::get($columns);

		$connection = $this->model->getConnectionName();

		$models = array();

		// Once we have the results we can spin through them and instantiate a new
		// model instance for each record we retrieved from the database. We'll
		// also set the proper connection name for the model after creating.
		foreach ($results as $result)
		{
			$model = $this->model->newExisting($result);

			$model->setConnection($connection);

			$models[] = $model;
		}

		// If we actually found models we will also eager load any relationships
		// that have been specified as needing to be eager loaded. This will
		// solve the n + 1 query problem for the developers conveniently.
		if (count($models) > 0)
		{
			$models = $this->eagerLoadRelations($models);
		}

		return new Collection($models);
	}

	/**
	 * Eager load the relationships for the models.
	 *
	 * @param  array  $models
	 * @return array
	 */
	protected function eagerLoadRelations(array $models)
	{
		foreach ($this->eagerLoad as $relation => $constraints)
		{
			// For nested eager loads we'll skip loading them here and they will be set as an
			// eager load on the query to retrieve the relation so that they will be eager
			// loaded on that query, because that is where they get hydrated as models.
			if (strpos($relation, '.') === false)
			{
				$models = $this->eagerLoadRelation($models, $relation, $constraints);
			}
		}

		return $models;
	}

	/**
	 * Eager load a given relationship for the models.
	 *
	 * @param  array    $models
	 * @param  string   $relation
	 * @param  Closure  $constraints
	 * @return array
	 */
	protected function eagerLoadRelation(array $models, $relation, Closure $constraints)
	{
		// First we will simply get the relationship instances from the top-level model.
		// Then we can set the necessary constraints on that instance to get all of
		// the models for the eager load querys, hydrating those on each parent.
		$instance = $this->getRelation($relation);

		list($wheres, $bindings) = $instance->getAndResetWheres();

		$instance->addEagerConstraints($models);

		$instance->mergeWheres($wheres, $bindings);

		// We allow the developers to specify constraints on eager loads and we'll just
		// call the constraints Closure, passing along the query so they can simply
		// do all they wish to the queriea, even specifying limits, orders, etc.
		call_user_func($constraints, $instance);

		$models = $instance->initRelation($models, $relation);

		return $instance->match($relation, $models, $instance->get());
	}

	/**
	 * Get the relation instance for the given relation name.
	 *
	 * @param  string  $relation
	 * @return Illuminate\Database\Eloquent\Relations\Relation
	 */
	protected function getRelation($relation)
	{
		$query = $this->query->getModel()->$relation();

		return $query->with($this->nestedRelations($relation));
	}

	/**
	 * Get the deeply nested relations for a given top-level relation.
	 *
	 * @param  string  $relation
	 * @return array
	 */
	protected function nestedRelations($relation)
	{
		$nested = array();

		// We are basically looking for any relationships that are nested deeper than
		// the given top-level relationship. We will just check for any relations
		// that start with the given top relation and adds them to our arrays.
		foreach ($this->eagerLoad as $name => $constraints)
		{
			if (strpos($name, $relation) === 0)
			{
				$nested[substr($name, strlen($relation.'.'))] = $constraints;
			}
		}

		return $nested;
	}

	/**
	 * Set the relationships that should be eaager loaded.
	 *
	 * @param  array  $relations
	 * @return Illuminate\Database\Eloquent\Builder
	 */
	public function with(array $relations)
	{
		$this->eagerLoad = $this->parseRelations($relations);

		return $this;
	}

	/**
	 * Parse a list of relations into individuals.
	 *
	 * @param  array  $relations
	 * @return array
	 */
	protected function parseRelations(array $relations)
	{
		$results = array();

		foreach ($relations as $relation => $constraints)
		{
			// If the "relation" value is actually a numeric key, we can assume that no constraints
			// have been specified for the eager load, and we will just attach an empty Closure
			// to the eager load so that we can treat all constraints as having eager loads.
			if (is_numeric($relation))
			{
				list($relation, $constraints) = array($constraints, function() {});
			}

			$progress = array();

			// We need to separate out any nested includes. This allows the developer to load deep
			// relatoinships using dots without specifying each level of the relationship with
			// its own key in the array. We will only set original constraints on the last.
			foreach (explode('.', $relation) as $segment)
			{
				$progress[] = $segment;

				$results[$last = implode('.', $progress)] = function() {};
			}

			// The eager load could have had constrains specified on it. We will put them on the
			// last eager load segment. This means that for a nested eager load include that
			// is loading multiple relationships only the last segments are constrained.
			$results[$last] = $constraints;
		}

		return $results;
	}

	/**
	 * Get the relationships being eagerly laoded.
	 *
	 * @return array
	 */
	public function getEagerLoads()
	{
		return $this->eagerLoad;
	}

	/**
	 * Get the model instance being queried.
	 *
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * Set a model instance for the model being queried.
	 *
	 * @param  Illuminate\Database\Eloquent\Model  $model
	 * @return void
	 */
	public function setModel(Model $model)
	{
		$this->model = $model;

		$this->from($model->getTable());
	}

}