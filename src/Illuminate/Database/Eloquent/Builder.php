<?php namespace Illuminate\Database\Eloquent;

use Illuminate\Database\Query\Builder as BaseBuilder;

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
		$results = parent::get($columns);

		$connection = $this->model->getConnectionName();

		$models = array();

		foreach ($results as $result)
		{
			$model = $this->model->newInstance((array) $result);

			$model->setConnection($connection);

			$model->exists = true;

			$models[] = $model;
		}

		return new Collection($models);
	}

	/**
	 * Set the relationships that should be eaager loaded.
	 *
	 * @param  array  $relations
	 * @return Illuminate\Database\Eloquent\Builder
	 */
	public function with(array $relations)
	{
		$this->eagerLoad = $relations;

		return $this;
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