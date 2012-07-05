<?php namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

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
		parent::__construct($query, $parent);

		$this->foreignKey = $foreignKey;
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

		$foreign = $this->parent->{$this->foreignKey};

		$this->query->where($key, '=', $this->parent->$foreign);
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
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	public function getResults()
	{
		return $this->query->first();
	}

}