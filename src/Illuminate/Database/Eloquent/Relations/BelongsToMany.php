<?php namespace Illuminate\Database\Eloquent\Relations;

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