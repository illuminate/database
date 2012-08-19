<?php namespace Illuminate\Database\Connectors;

abstract class Connector {

	/**
	 * Establish a database connection.
	 *
	 * @param  array  $options
	 * @return PDO
	 */
	abstract public function connect(array $options);

}