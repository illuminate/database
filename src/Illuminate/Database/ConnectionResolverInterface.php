<?php namespace Illuminate\Database;

interface ConnectionResolverInterface {

	/**
	 * Get a database connection instance.
	 *
	 * @param  string  $name
	 * @return Illuminate\Database\Connection
	 */
	public function connection($name = null);

}