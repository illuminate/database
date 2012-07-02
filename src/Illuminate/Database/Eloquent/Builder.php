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
	 * Find a model by its primary key.
	 *
	 * @param  mixed  $id
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function find($id)
	{
		$key = $this->model->getKeyName();

		$this->where($key, '=', $this->model->getKey());

		return $this->first();
	}

	/**
	 * Execute the query and get the first result.
	 *
	 * @param  array   $columns
	 * @return array
	 */
	public function first($columns = array('*'))
	{
		$result = parent::first($columns);

		if ( ! is_null($result))
		{
			return $this->model->newInstance($result);
		}
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