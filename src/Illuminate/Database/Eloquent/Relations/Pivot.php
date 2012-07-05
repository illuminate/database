<?php namespace Illuminate\Database\Eloquent\Relations;

use Illuminate\Database\Eloquent\Model;

class Pivot extends Model {

	/**
	 * Create a new pivot model instance.
	 *
	 * @param  array   $attributes
	 * @param  string  $table
	 * @param  string  $connection
	 * @return void
	 */
	public function __construct($attributes, $table, $connection)
	{
		parent::__construct($attributes);

		$this->setTable($table);

		$this->setConnection($connection);
	}

}