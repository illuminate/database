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
		$models = $this->query->getModels($this->getSelectColumns());

		$this->hydratePivotRelation($models);

		$collection = $this->related->newCollection($models);

		// If we actually found models we will also eager load any relationships that
		// have been specified as needing to be eager loaded. This will solve the
		// n + 1 query problem for the developer and also increase performance.
		if (count($models) > 0)
		{
			$models = $this->query->eagerLoadRelations($collection);
		}

		return $collection;
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

		foreach ($model->getAttributes() as $key => $value)
		{
			// To get the pivots attributes we will just take any of the attributes which
			// begin with "pivot_" and add those to this arrays, as well as unsetting
			// them from the parent's models since they exist in a different table.
			if (strpos($key, 'pivot_') === 0)
			{
				$values[substr($key, 6)] = $value;

				unset($model->$key);
			}
		}

		return $values;
	}

	/**
	 * Set the base constraints on the relation query.
	 *
	 * @return void
	 */
	public function addConstraints()
	{
		$this->setJoin()->setWhere();
	}

	/**
	 * Set the select clause for the relation query.
	 *
	 * @return Illuminate\Database\Eloquent\Relation\BelongsToMany
	 */
	protected function getSelectColumns()
	{
		$columns = array($this->related->getTable().'.*');

		return array_merge($columns, $this->getAliasedPivotColumns());
	}

	/**
	 * Get the pivot columns for the relation.
	 *
	 * @return array
	 */
	protected function getAliasedPivotColumns()
	{
		$defaults = array($this->foreignKey, $this->otherKey);

		// We need to alias all of the pivot columns with the "pivot_" prefix so we
		// can easily extract them out of the models and put them into the pivot
		// relationships when they are retrieved and hydrated into the models.
		$columns = array();

		foreach (array_merge($defaults, $this->pivotColumns) as $column)
		{
			$columns[] = $this->table.'.'.$column.' as pivot_'.$column;
		}

		return array_unique($columns);
	}

	/**
	 * Set the join clause for the relation query.
	 *
	 * @return Illuminate\Database\Eloquent\Relation\BelongsToMany
	 */
	protected function setJoin()
	{
		// We need to join to the intermediate table on the related model's primary
		// key column with the intermediate table's foreign key for the related
		// model instance. Then we can set the "where" for the parent models.
		$baseTable = $this->related->getTable();

		$key = $baseTable.'.'.$this->related->getKeyName();

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
			$model->setRelation($relation, $this->related->newCollection());
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
		$dictionary = $this->buildDictionary($results);

		// Once we have an array dictionary of child objects we can easily match the
		// children back to their parent using the dictionary and the keys on the
		// the parent models. Then we will return the hydrated models back out.
		foreach ($models as $model)
		{;
			if (isset($dictionary[$key = $model->getKey()]))
			{
				$collection = $this->related->newCollection($dictionary[$key]);

				$model->setRelation($relation, $collection);
			}
		}

		return $models;
	}

	/**
	 * Build model dictionary keyed by the relation's foreign key.
	 *
	 * @param  Illuminate\Database\Eloquent\Collection  $results
	 * @return array
	 */
	protected function buildDictionary(Collection $results)
	{
		$foreign = $this->foreignKey;

		// First we will build a dictionary of child models keyed by the foreign key
		// of the relation so that we will easily and quickly match them to their
		// parents without having a possibly slow inner loops for every models.
		$dictionary = array();

		foreach ($results as $result)
		{
			$dictionary[$result->pivot->$foreign][] = $result;
		}

		return $dictionary;
	}

	/**
	 * Sync the intermediate tables with a list of IDs.
	 *
	 * @param  array  $ids
	 * @return void
	 */
	public function sync(array $ids)
	{
		// First we need to attach any of the associated models that are not currently
		// in this joining table. We'll spin through the given IDs, checking to see
		// if they exist in the array of current ones, and if not we will insert.
		$current = $this->newPivotQuery()->lists($this->otherKey);

		foreach ($ids as $id)
		{
			if ( ! in_array($id, $current))
			{
				$this->attach($id);
			}
		}

		// Next, we will take the differences of the currents and given IDs and detach
		// all of the entities that exist in the "current" array but are not in the
		// the array of the IDs given to the method which will complete the sync.
		$detach = array_diff($current, $ids);

		if (count($detach) > 0)
		{
			$this->detach($detach);
		}
	}

	/**
	 * Create a new instance of the related model.
	 *
	 * @param  array  $attributes
	 * @param  array  $joining
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function create(array $attributes, array $joining = array())
	{
		$instance = $this->related->newInstance($attributes);

		// Once we save the related model, we need to attach it to the base model via
		// through intermediate table so we'll use the existing "attach" method to
		// accomplish this which will insert the record and any more attributes.
		$instance->save();

		$this->attach($instance->getKey(), $joining);

		return $instance;
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
		if ($id instanceof Model) $id = $id->getKey();

		$query = $this->query->newQuery()->from($this->table);

		return $query->insert($this->createAttachRecords((array) $id, $attributes));
	}

	/**
	 * Create an array of records to insert into the pivot table.
	 *
	 * @param  array  $ids
	 * @return void
	 */
	protected function createAttachRecords($ids, array $attributes)
	{
		$records = array();

		$hasTimestamps = in_array('created_at', $this->pivotColumns);

		// To create the attachment records, we will simply spin through the IDs given
		// and create a new record to insert for each ID. Each ID may actually be a
		// key in the array, with extra attributes to be placed in other columns.
		foreach ($ids as $key => $value)
		{
			list($id, $extra) = $this->getAttachId($key, $value, $attributes);

			$record = $this->createAttachRecord($id, $hasTimestamps);

			$records[] = array_merge($record, $extra);
		}

		return $records;
	}

	/**
	 * Get the attach record ID and extra attributes.
	 *
	 * @param  mixed  $key
	 * @param  mixed  $value
	 * @param  array  $attributes
	 * @return array
	 */
	protected function getAttachId($key, $value, array $attributes)
	{
		if (is_array($value))
		{
			return array($key, array_merge($value, $attributes));
		}
		else
		{
			return array($value, $attributes);
		}
	}

	/**
	 * Create a new pivot attachment record.
	 *
	 * @param  int   $id
	 * @param  bool  $hasTimestamps
	 * @return array
	 */
	protected function createAttachRecord($id, $hasTimestamps)
	{
		$record[$this->foreignKey] = $this->parent->getKey();

		$record[$this->otherKey] = $id;

		// If the record needs to have creation and update timestamps, we will make
		// them by calling the parent model's "freshTimestamp" method which will
		// provide us with a fresh timestamp in this model's preferred format.
		if ($hasTimestamps)
		{
			$record['created_at'] = $this->parent->freshTimestamp();

			$record['updated_at'] = $record['created_at'];
		}

		return $record;
	}

	/**
	 * Detach models from the relationship.
	 *
	 * @param  int|array  $ids
	 * @return int
	 */
	public function detach($ids = array())
	{
		if ($ids instanceof Model) $ids = (array) $ids->getKey();

		$query = $this->newPivotQuery();

		// If associated IDs were passed to the method we will only delete those
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
	 * Create a new query builder for the pivot table.
	 *
	 * @return Illuminate\Database\Eloquent\Builder
	 */
	protected function newPivotQuery()
	{
		$query = $this->query->newQuery()->from($this->table);

		return $query->where($this->foreignKey, $this->parent->getKey());
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

		$pivot = new Pivot($attributes, $this->table, $connection, $exists);

		$pivot->setPivotKeys($this->foreignKey, $this->otherKey);

		return $pivot;
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
		$this->pivotColumns = is_array($columns) ? $columns : func_get_args();

		return $this;
	}

	/**
	 * Specify that the pivot table has creation and update timestamps.
	 *
	 * @return Illuminate\Database\Eloquent\Relations\BelongsToMany
	 */
	public function withTimestamps()
	{
		$columns = array('created_at', 'updated_at');

		$this->pivotColumns = array_merge($this->pivotColumns, $columns);

		return $this;
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
	 * Get the intermediate table for the relationship.
	 *
	 * @return string
	 */
	public function getTable()
	{
		return $this->table;
	}

}