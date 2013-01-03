<?php namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

abstract class MorphOneOrMany extends HasOneOrMany {

	/**
	 * The foreign key type for the relationship.
	 *
	 * @var string
	 */
	protected $morphType;

	/**
	 * The class name of the parent model.
	 *
	 * @var string
	 */
	protected $morphClass;

	/**
	 * Create a new has many relationship instance.
	 *
	 * @param  Illuminate\Database\Eloquent\Builder  $query
	 * @param  Illuminate\Database\Eloquent\Model  $parent
	 * @param  string  $morphName
	 * @return void
	 */
	public function __construct(Builder $query, Model $parent, $morphName)
	{
		$this->morphType = "{$morphName}_type";

		$this->morphClass = get_class($parent);

		parent::__construct($query, $parent, "{$morphName}_id");
	}

	/**
	 * Set the base constraints on the relation query.
	 *
	 * @return void
	 */
	public function addConstraints()
	{
		parent::addConstraints();

		$this->query->where($this->morphType, $this->morphClass);
	}

	/**
	 * Set the constraints for an eager load of the relation.
	 *
	 * @param  array  $models
	 * @return void
	 */
	public function addEagerConstraints(array $models)
	{
		parent::addEagerConstraints($models);

		$this->query->where($this->morphType, $this->morphClass);
	}	

	/**
	 * Remove the original where clause set by the relationship.
	 *
	 * The remaining constraints on the query will be reset and returned.
	 *
	 * @return array
	 */
	public function getAndResetWheres()
	{
		// We actually need to remove two where clauses from polymorphic queries so we
		// will make an extra call to remove the first where clause here so that we
		// remove two total where clause from the query leaving only custom ones.
		$this->removeFirstWhereClause();

		return parent::getAndResetWheres();
	}

	/**
	 * Create a new instance of the related model.
	 *
	 * @param  array  $attributes
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function create(array $attributes)
	{
		$foreign = array($this->foreignKey => $this->parent->getKey());

		// When saving a polymorphic relationship, we need to set not only the foreign
		// key, but also the foreign key type, which is typically the class name of
		// the parent model. This makes the polymorphic item unique in the table.
		$foreign[$this->morphType] = $this->morphClass;

		$attributes = array_merge($attributes, $foreign);

		$instance = $this->related->newInstance($attributes);

		$instance->save();

		return $instance;
	}

	/**
	 * Get the foreign key "type" name.
	 *
	 * @return string
	 */
	public function getMorphType()
	{
		return $this->morphType;
	}

	/**
	 * Get the class name of the parent model.
	 *
	 * @return string
	 */
	public function getMorphClass()
	{
		return $this->morphClass;
	}

}