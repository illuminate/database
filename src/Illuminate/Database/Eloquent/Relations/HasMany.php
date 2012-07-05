<?php namespace Illuminate\Database\Eloquent\Relations;

class HasMany extends HasOneOrMany {

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
	 * Initialize the relation on a set of models.
	 *
	 * @param  array   $models
	 * @param  string  $relation
	 * @return void
	 */
	public function initializeRelation(array $models, $relation)
	{
		foreach ($models as $model)
		{
			$model->setRelation($relation, array());
		}

		return $models;
	}

}