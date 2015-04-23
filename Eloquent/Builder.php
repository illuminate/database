<?php namespace Illuminate\Database\Eloquent;

use Closure;
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Query\Expression;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

class Builder {

	/**
	 * The base query builder instance.
	 *
	 * @var \Illuminate\Database\Query\Builder
	 */
	protected $query;

	/**
	 * The model being queried.
	 *
	 * @var \Illuminate\Database\Eloquent\Model
	 */
	protected $model;

	/**
	 * The relationships that should be eager loaded.
	 *
	 * @var array
	 */
	protected $eagerLoad = array();

	/**
	 * All of the registered builder macros.
	 *
	 * @var array
	 */
	protected $macros = array();

	/**
	 * A replacement for the typical delete function.
	 *
	 * @var \Closure
	 */
	protected $onDelete;

	/**
	 * The methods that should be returned from query builder.
	 *
	 * @var array
	 */
	protected $passthru = array(
		'toSql', 'lists', 'insert', 'insertGetId', 'pluck', 'count',
		'min', 'max', 'avg', 'sum', 'exists', 'getBindings',
	);

	/**
	 * Create a new Eloquent query builder instance.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @return void
	 */
	public function __construct(QueryBuilder $query)
	{
		$this->query = $query;
	}

	/**
	 * Find a model by its primary key.
	 *
	 * @param  mixed  $id
	 * @param  array  $columns
	 * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|null
	 */
	public function find($id, $columns = array('*'))
	{
		if (is_array($id))
		{
			return $this->findMany($id, $columns);
		}

		$this->query->where($this->model->getQualifiedKeyName(), '=', $id);

		return $this->first($columns);
	}

	/**
	 * Find a model by its primary key.
	 *
	 * @param  array  $ids
	 * @param  array  $columns
	 * @return \Illuminate\Database\Eloquent\Collection
	 */
	public function findMany($ids, $columns = array('*'))
	{
		if (empty($ids)) return $this->model->newCollection();

		$this->query->whereIn($this->model->getQualifiedKeyName(), $ids);

		return $this->get($columns);
	}

	/**
	 * Find a model by its primary key or throw an exception.
	 *
	 * @param  mixed  $id
	 * @param  array  $columns
	 * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
	 *
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
	 */
	public function findOrFail($id, $columns = array('*'))
	{
		$result = $this->find($id, $columns);

		if (is_array($id))
		{
			if (count($result) == count(array_unique($id))) return $result;
		}
		elseif ( ! is_null($result))
		{
			return $result;
		}

		throw (new ModelNotFoundException)->setModel(get_class($this->model));
	}

	/**
	 * Execute the query and get the first result.
	 *
	 * @param  array  $columns
	 * @return \Illuminate\Database\Eloquent\Model|static|null
	 */
	public function first($columns = array('*'))
	{
		return $this->take(1)->get($columns)->first();
	}

	/**
	 * Execute the query and get the first result or throw an exception.
	 *
	 * @param  array  $columns
	 * @return \Illuminate\Database\Eloquent\Model|static
	 *
	 * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
	 */
	public function firstOrFail($columns = array('*'))
	{
		if ( ! is_null($model = $this->first($columns))) return $model;

		throw (new ModelNotFoundException)->setModel(get_class($this->model));
	}

	/**
	 * Execute the query as a "select" statement.
	 *
	 * @param  array  $columns
	 * @return \Illuminate\Database\Eloquent\Collection|static[]
	 */
	public function get($columns = array('*'))
	{
		$models = $this->getModels($columns);

		// If we actually found models we will also eager load any relationships that
		// have been specified as needing to be eager loaded, which will solve the
		// n+1 query issue for the developers to avoid running a lot of queries.
		if (count($models) > 0)
		{
			$models = $this->eagerLoadRelations($models);
		}

		return $this->model->newCollection($models);
	}

	/**
	 * Pluck a single column from the database.
	 *
	 * @param  string  $column
	 * @return mixed
	 */
	public function pluck($column)
	{
		$result = $this->first(array($column));

		if ($result) return $result->{$column};
	}

	/**
	 * Chunk the results of the query.
	 *
	 * @param  int  $count
	 * @param  callable  $callback
	 * @return void
	 */
	public function chunk($count, callable $callback)
	{
		$results = $this->forPage($page = 1, $count)->get();

		while (count($results) > 0)
		{
			// On each chunk result set, we will pass them to the callback and then let the
			// developer take care of everything within the callback, which allows us to
			// keep the memory low for spinning through large result sets for working.
			call_user_func($callback, $results);

			$page++;

			$results = $this->forPage($page, $count)->get();
		}
	}

	/**
	 * Get an array with the values of a given column.
	 *
	 * @param  string  $column
	 * @param  string  $key
	 * @return array
	 */
	public function lists($column, $key = null)
	{
		$results = $this->query->lists($column, $key);

		// If the model has a mutator for the requested column, we will spin through
		// the results and mutate the values so that the mutated version of these
		// columns are returned as you would expect from these Eloquent models.
		if ($this->model->hasGetMutator($column))
		{
			foreach ($results as $key => &$value)
			{
				$fill = array($column => $value);

				$value = $this->model->newFromBuilder($fill)->$column;
			}
		}

		return $results;
	}

	/**
	 * Paginate the given query.
	 *
	 * @param  int  $perPage
	 * @param  array  $columns
	 * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
	 */
	public function paginate($perPage = null, $columns = ['*'])
	{
		$total = $this->query->getCountForPagination();

		$this->query->forPage(
			$page = Paginator::resolveCurrentPage(),
			$perPage = $perPage ?: $this->model->getPerPage()
		);

		return new LengthAwarePaginator($this->get($columns), $total, $perPage, $page, [
			'path' => Paginator::resolveCurrentPath(),
		]);
	}

	/**
	 * Paginate the given query into a simple paginator.
	 *
	 * @param  int  $perPage
	 * @param  array  $columns
	 * @return \Illuminate\Contracts\Pagination\Paginator
	 */
	public function simplePaginate($perPage = null, $columns = ['*'])
	{
		$page = Paginator::resolveCurrentPage();

		$perPage = $perPage ?: $this->model->getPerPage();

		$this->skip(($page - 1) * $perPage)->take($perPage + 1);

		return new Paginator($this->get($columns), $perPage, $page, [
			'path' => Paginator::resolveCurrentPath(),
		]);
	}

	/**
	 * Update a record in the database.
	 *
	 * @param  array  $values
	 * @return int
	 */
	public function update(array $values)
	{
		return $this->query->update($this->addUpdatedAtColumn($values));
	}

	/**
	 * Increment a column's value by a given amount.
	 *
	 * @param  string  $column
	 * @param  int     $amount
	 * @param  array   $extra
	 * @return int
	 */
	public function increment($column, $amount = 1, array $extra = array())
	{
		$extra = $this->addUpdatedAtColumn($extra);

		return $this->query->increment($column, $amount, $extra);
	}

	/**
	 * Decrement a column's value by a given amount.
	 *
	 * @param  string  $column
	 * @param  int     $amount
	 * @param  array   $extra
	 * @return int
	 */
	public function decrement($column, $amount = 1, array $extra = array())
	{
		$extra = $this->addUpdatedAtColumn($extra);

		return $this->query->decrement($column, $amount, $extra);
	}

	/**
	 * Add the "updated at" column to an array of values.
	 *
	 * @param  array  $values
	 * @return array
	 */
	protected function addUpdatedAtColumn(array $values)
	{
		if ( ! $this->model->usesTimestamps()) return $values;

		$column = $this->model->getUpdatedAtColumn();

		return array_add($values, $column, $this->model->freshTimestampString());
	}

	/**
	 * Delete a record from the database.
	 *
	 * @return mixed
	 */
	public function delete()
	{
		if (isset($this->onDelete))
		{
			return call_user_func($this->onDelete, $this);
		}

		return $this->query->delete();
	}

	/**
	 * Run the default delete function on the builder.
	 *
	 * @return mixed
	 */
	public function forceDelete()
	{
		return $this->query->delete();
	}

	/**
	 * Register a replacement for the default delete function.
	 *
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function onDelete(Closure $callback)
	{
		$this->onDelete = $callback;
	}

	/**
	 * Get the hydrated models without eager loading.
	 *
	 * @param  array  $columns
	 * @return \Illuminate\Database\Eloquent\Model[]
	 */
	public function getModels($columns = array('*'))
	{
		$results = $this->query->get($columns);

		$connection = $this->model->getConnectionName();

		return $this->model->hydrate($results, $connection)->all();
	}

	/**
	 * Eager load the relationships for the models.
	 *
	 * @param  array  $models
	 * @return array
	 */
	public function eagerLoadRelations(array $models)
	{
		foreach ($this->eagerLoad as $name => $constraints)
		{
			// For nested eager loads we'll skip loading them here and they will be set as an
			// eager load on the query to retrieve the relation so that they will be eager
			// loaded on that query, because that is where they get hydrated as models.
			if (strpos($name, '.') === false)
			{
				$models = $this->loadRelation($models, $name, $constraints);
			}
		}

		return $models;
	}

	/**
	 * Eagerly load the relationship on a set of models.
	 *
	 * @param  array     $models
	 * @param  string    $name
	 * @param  \Closure  $constraints
	 * @return array
	 */
	protected function loadRelation(array $models, $name, Closure $constraints)
	{
		// First we will "back up" the existing where conditions on the query so we can
		// add our eager constraints. Then we will merge the wheres that were on the
		// query back to it in order that any where conditions might be specified.
		$relation = $this->getRelation($name);

		$relation->addEagerConstraints($models);

		call_user_func($constraints, $relation);

		$models = $relation->initRelation($models, $name);

		// Once we have the results, we just match those back up to their parent models
		// using the relationship instance. Then we just return the finished arrays
		// of models which have been eagerly hydrated and are readied for return.
		$results = $relation->getEager();

		return $relation->match($models, $results, $name);
	}

	/**
	 * Get the relation instance for the given relation name.
	 *
	 * @param  string  $relation
	 * @return \Illuminate\Database\Eloquent\Relations\Relation
	 */
	public function getRelation($relation)
	{
		// We want to run a relationship query without any constrains so that we will
		// not have to remove these where clauses manually which gets really hacky
		// and is error prone while we remove the developer's own where clauses.
		$query = Relation::noConstraints(function() use ($relation)
		{
			return $this->getModel()->$relation();
		});

		$nested = $this->nestedRelations($relation);

		// If there are nested relationships set on the query, we will put those onto
		// the query instances so that they can be handled after this relationship
		// is loaded. In this way they will all trickle down as they are loaded.
		if (count($nested) > 0)
		{
			$query->getQuery()->with($nested);
		}

		return $query;
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
		// that start with the given top relations and adds them to our arrays.
		foreach ($this->eagerLoad as $name => $constraints)
		{
			if ($this->isNested($name, $relation))
			{
				$nested[substr($name, strlen($relation.'.'))] = $constraints;
			}
		}

		return $nested;
	}

	/**
	 * Determine if the relationship is nested.
	 *
	 * @param  string  $name
	 * @param  string  $relation
	 * @return bool
	 */
	protected function isNested($name, $relation)
	{
		$dots = str_contains($name, '.');

		return $dots && starts_with($name, $relation.'.');
	}

	/**
	 * Add a basic where clause to the query.
	 *
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @param  string  $boolean
	 * @return $this
	 */
	public function where($column, $operator = null, $value = null, $boolean = 'and')
	{
		if ($column instanceof Closure)
		{
			$query = $this->model->newQueryWithoutScopes();

			call_user_func($column, $query);

			$this->query->addNestedWhereQuery($query->getQuery(), $boolean);
		}
		else
		{
			call_user_func_array(array($this->query, 'where'), func_get_args());
		}

		return $this;
	}

	/**
	 * Add an "or where" clause to the query.
	 *
	 * @param  string  $column
	 * @param  string  $operator
	 * @param  mixed   $value
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public function orWhere($column, $operator = null, $value = null)
	{
		return $this->where($column, $operator, $value, 'or');
	}

	/**
	 * Add a relationship count condition to the query.
	 *
	 * @param  string  $relation
	 * @param  string  $operator
	 * @param  int     $count
	 * @param  string  $boolean
	 * @param  \Closure|null  $callback
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public function has($relation, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null)
	{
		if (strpos($relation, '.') !== false)
		{
			return $this->hasNested($relation, $operator, $count, $boolean, $callback);
		}

		$relation = $this->getHasRelationQuery($relation);

		$query = $relation->getRelationCountQuery($relation->getRelated()->newQuery(), $this);

		if ($callback) call_user_func($callback, $query);

		return $this->addHasWhere($query, $relation, $operator, $count, $boolean);
	}

	/**
	 * Add nested relationship count conditions to the query.
	 *
	 * @param  string  $relations
	 * @param  string  $operator
	 * @param  int     $count
	 * @param  string  $boolean
	 * @param  \Closure  $callback
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	protected function hasNested($relations, $operator = '>=', $count = 1, $boolean = 'and', $callback = null)
	{
		$relations = explode('.', $relations);

		// In order to nest "has", we need to add count relation constraints on the
		// callback Closure. We'll do this by simply passing the Closure its own
		// reference to itself so it calls itself recursively on each segment.
		$closure = function ($q) use (&$closure, &$relations, $operator, $count, $boolean, $callback)
		{
			if (count($relations) > 1)
			{
				$q->whereHas(array_shift($relations), $closure);
			}
			else
			{
				$q->has(array_shift($relations), $operator, $count, 'and', $callback);
			}
		};

		return $this->has(array_shift($relations), '>=', 1, $boolean, $closure);
	}

	/**
	 * Add a relationship count condition to the query.
	 *
	 * @param  string  $relation
	 * @param  string  $boolean
	 * @param  \Closure|null  $callback
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public function doesntHave($relation, $boolean = 'and', Closure $callback = null)
	{
		return $this->has($relation, '<', 1, $boolean, $callback);
	}

	/**
	 * Add a relationship count condition to the query with where clauses.
	 *
	 * @param  string    $relation
	 * @param  \Closure  $callback
	 * @param  string    $operator
	 * @param  int       $count
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public function whereHas($relation, Closure $callback, $operator = '>=', $count = 1)
	{
		return $this->has($relation, $operator, $count, 'and', $callback);
	}

	/**
	 * Add a relationship count condition to the query with where clauses.
	 *
	 * @param  string  $relation
	 * @param  \Closure|null  $callback
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public function whereDoesntHave($relation, Closure $callback = null)
	{
		return $this->doesntHave($relation, 'and', $callback);
	}

	/**
	 * Add a relationship count condition to the query with an "or".
	 *
	 * @param  string  $relation
	 * @param  string  $operator
	 * @param  int     $count
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public function orHas($relation, $operator = '>=', $count = 1)
	{
		return $this->has($relation, $operator, $count, 'or');
	}

	/**
	 * Add a relationship count condition to the query with where clauses and an "or".
	 *
	 * @param  string    $relation
	 * @param  \Closure  $callback
	 * @param  string    $operator
	 * @param  int       $count
	 * @return \Illuminate\Database\Eloquent\Builder|static
	 */
	public function orWhereHas($relation, Closure $callback, $operator = '>=', $count = 1)
	{
		return $this->has($relation, $operator, $count, 'or', $callback);
	}

	/**
	 * Add the "has" condition where clause to the query.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $hasQuery
	 * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
	 * @param  string  $operator
	 * @param  int  $count
	 * @param  string  $boolean
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	protected function addHasWhere(Builder $hasQuery, Relation $relation, $operator, $count, $boolean)
	{
		$this->mergeWheresToHas($hasQuery, $relation);

		if (is_numeric($count))
		{
			$count = new Expression($count);
		}

		return $this->where(new Expression('('.$hasQuery->toSql().')'), $operator, $count, $boolean);
	}

	/**
	 * Merge the "wheres" from a relation query to a has query.
	 *
	 * @param  \Illuminate\Database\Eloquent\Builder  $hasQuery
	 * @param  \Illuminate\Database\Eloquent\Relations\Relation  $relation
	 * @return void
	 */
	protected function mergeWheresToHas(Builder $hasQuery, Relation $relation)
	{
		// Here we have the "has" query and the original relation. We need to copy over any
		// where clauses the developer may have put in the relationship function over to
		// the has query, and then copy the bindings from the "has" query to the main.
		$relationQuery = $relation->getBaseQuery();

		$hasQuery = $hasQuery->getModel()->removeGlobalScopes($hasQuery);

		$hasQuery->mergeWheres(
			$relationQuery->wheres, $relationQuery->getBindings()
		);

		$this->query->mergeBindings($hasQuery->getQuery());
	}

	/**
	 * Get the "has relation" base query instance.
	 *
	 * @param  string  $relation
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
	protected function getHasRelationQuery($relation)
	{
		return Relation::noConstraints(function() use ($relation)
		{
			return $this->getModel()->$relation();
		});
	}

	/**
	 * Set the relationships that should be eager loaded.
	 *
	 * @param  mixed  $relations
	 * @return $this
	 */
	public function with($relations)
	{
		if (is_string($relations)) $relations = func_get_args();

		$eagers = $this->parseRelations($relations);

		$this->eagerLoad = array_merge($this->eagerLoad, $eagers);

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

		foreach ($relations as $name => $constraints)
		{
			// If the "relation" value is actually a numeric key, we can assume that no
			// constraints have been specified for the eager load and we'll just put
			// an empty Closure with the loader so that we can treat all the same.
			if (is_numeric($name))
			{
				$f = function() {};

				list($name, $constraints) = array($constraints, $f);
			}

			// We need to separate out any nested includes. Which allows the developers
			// to load deep relationships using "dots" without stating each level of
			// the relationship with its own key in the array of eager load names.
			$results = $this->parseNested($name, $results);

			$results[$name] = $constraints;
		}

		return $results;
	}

	/**
	 * Parse the nested relationships in a relation.
	 *
	 * @param  string  $name
	 * @param  array   $results
	 * @return array
	 */
	protected function parseNested($name, $results)
	{
		$progress = array();

		// If the relation has already been set on the result array, we will not set it
		// again, since that would override any constraints that were already placed
		// on the relationships. We will only set the ones that are not specified.
		foreach (explode('.', $name) as $segment)
		{
			$progress[] = $segment;

			if ( ! isset($results[$last = implode('.', $progress)]))
			{
				$results[$last] = function() {};
			}
		}

		return $results;
	}

	/**
	 * Call the given model scope on the underlying model.
	 *
	 * @param  string  $scope
	 * @param  array   $parameters
	 * @return \Illuminate\Database\Query\Builder
	 */
	protected function callScope($scope, $parameters)
	{
		array_unshift($parameters, $this);

		return call_user_func_array(array($this->model, $scope), $parameters) ?: $this;
	}

	/**
	 * Get the underlying query builder instance.
	 *
	 * @return \Illuminate\Database\Query\Builder|static
	 */
	public function getQuery()
	{
		return $this->query;
	}

	/**
	 * Set the underlying query builder instance.
	 *
	 * @param  \Illuminate\Database\Query\Builder  $query
	 * @return $this
	 */
	public function setQuery($query)
	{
		$this->query = $query;

		return $this;
	}

	/**
	 * Get the relationships being eagerly loaded.
	 *
	 * @return array
	 */
	public function getEagerLoads()
	{
		return $this->eagerLoad;
	}

	/**
	 * Set the relationships being eagerly loaded.
	 *
	 * @param  array  $eagerLoad
	 * @return $this
	 */
	public function setEagerLoads(array $eagerLoad)
	{
		$this->eagerLoad = $eagerLoad;

		return $this;
	}

	/**
	 * Get the model instance being queried.
	 *
	 * @return \Illuminate\Database\Eloquent\Model
	 */
	public function getModel()
	{
		return $this->model;
	}

	/**
	 * Set a model instance for the model being queried.
	 *
	 * @param  \Illuminate\Database\Eloquent\Model  $model
	 * @return $this
	 */
	public function setModel(Model $model)
	{
		$this->model = $model;

		$this->query->from($model->getTable());

		return $this;
	}

	/**
	 * Extend the builder with a given callback.
	 *
	 * @param  string    $name
	 * @param  \Closure  $callback
	 * @return void
	 */
	public function macro($name, Closure $callback)
	{
		$this->macros[$name] = $callback;
	}

	/**
	 * Get the given macro by name.
	 *
	 * @param  string  $name
	 * @return \Closure
	 */
	public function getMacro($name)
	{
		return array_get($this->macros, $name);
	}

	/**
	 * Dynamically handle calls into the query instance.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if (isset($this->macros[$method]))
		{
			array_unshift($parameters, $this);

			return call_user_func_array($this->macros[$method], $parameters);
		}
		elseif (method_exists($this->model, $scope = 'scope'.ucfirst($method)))
		{
			return $this->callScope($scope, $parameters);
		}

		$result = call_user_func_array(array($this->query, $method), $parameters);

		return in_array($method, $this->passthru) ? $result : $this;
	}

	/**
	 * Force a clone of the underlying query builder when cloning.
	 *
	 * @return void
	 */
	public function __clone()
	{
		$this->query = clone $this->query;
	}

}
