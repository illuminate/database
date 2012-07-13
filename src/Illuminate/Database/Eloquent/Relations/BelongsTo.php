<?php namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class BelongsTo extends Relation {

	/**
	 * The foreign key of the parent model.
	 *
	 * @var string
	 */
	protected $foreignKey;

	/**
	 * Create a new has many relationship instance.
	 *
	 * @param  Illuminate\Database\Eloquent\Builder  $query
	 * @param  Illuminate\Database\Eloquent\Model  $parent
	 * @param  string  $foreignKey
	 * @return void
	 */
	public function __construct(Builder $query, Model $parent, $foreignKey)
	{
		$this->foreignKey = $foreignKey;

		parent::__construct($query, $parent);
	}

	/**
	 * Update the parent model on the relationship.
	 *
	 * @param  array  $attributes
	 * @return mixed
	 */
	public function update(array $attributes)
	{
		$instance = $this->getResults();

		return $instance->fill($attributes)->save();
	}

	/**
	 * Perform an update on the parent model without retrieving it.
	 *
	 * @param  array  $attributes
	 * @return int
	 */
	public function fastUpdate(array $attributes)
	{
		return $this->query->update($attributes);
	}

	/**
	 * Set the base constraints on the relation query.
	 *
	 * @return void
	 */
	public function addConstraints()
	{
		// For belongs to relationships, which are essentially the inverse of has one
		// or has many relationships, we need to actually query on the primary key
		// of the related model matching on the foreign key thats on the parent.
		$key = $this->related->getKeyName();

		$this->query->where($key, '=', $this->parent->{$this->foreignKey});
	}

	/**
	 * Set the constraints for an eager load of the relation.
	 *
	 * @param  array  $models
	 * @return void
	 */
	public function addEagerConstraints(array $models)
	{
		$keys = array();

		// First we need to gather all of the keys from the parent models so we know what
		// to query for in the eager loading query. We will add them to an array then
		// execute a where in statement to gather up all of those related records.
		foreach ($models as $model)
		{
			if ( ! is_null($value = $model->{$this->foreignKey}))
			{
				$keys[] = $value;
			}
		}

		// We'll grab the primary key name of the related model since it could be set to
		// a non-standard name and not "id". We'll then construct the constraint for
		// our eager loading query so it returns the proper models on exeuction.
		$key = $this->related->getKeyName();

		$this->query->whereIn($key, array_unique($keys));
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
			$model->setRelation($relation, null);
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
		$foreign = $this->foreignKey;

		// First we will get to build a dictionary of the child models by their primary
		// key of the relationship, then we can easily match the children back onto
		// the parents using that dictionary and the primary key of the children.
		$key = $this->related->getKeyName();

		$dictionary = array();

		foreach ($results as $result)
		{
			$dictionary[$result->getKey()] = $result;
		}

		// Once we have the dictionary constructed, we can loop through all the parents
		// and match back onto their children using these keys of the dictionary and
		// the primary key of the children to map them onto the correct instances.
		foreach ($models as $model)
		{
			if (isset($dictionary[$model->$foreign]))
			{
				$model->setRelation($relation, $dictionary[$model->$foreign]);
			}
		}

		return $models;
	}

	/**
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	public function getResults()
	{
		return $this->query->first();
	}

	/**
	 * Get the foreign key of the relationship.
	 *
	 * @return string
	 */
	public function getForeignKey()
	{
		return $this->foreignKey;
	}

}