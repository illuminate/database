<?php namespace Illuminate\Database\Eloquent\Relations;

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
	 * Get the results of the relationship.
	 *
	 * @return mixed
	 */
	public function getResults()
	{
		return $this->query->first();
	}

}