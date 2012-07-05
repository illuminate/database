<?php namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;

class Pivot extends Model {

	/**
	 * Create a new pivot model instance.
	 *
	 * @param  array   $attributes
	 * @param  string  $table
	 * @param  string  $connection
	 * @param  bool    $exists
	 * @return void
	 */
	public function __construct($attributes, $table, $connection, $exists = false)
	{
		// The pivot model is a "dynamic" model since we will set the table dynamically
		// for the instance. This allows it work for any intermediate table for the
		// many to many relationship that are defined by the developer's models.
		parent::__construct($attributes);

		$this->setTable($table);

		$this->setConnection($connection);

		$this->exists = $exists;
	}

}