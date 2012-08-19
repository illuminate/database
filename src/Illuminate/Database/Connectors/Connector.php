<?php namespace Illuminate\Database\Connectors;

use PDO;

abstract class Connector {

	/**
	 * The default PDO connection options.
	 *
	 * @var array
	 */
	protected $options = array(
			PDO::ATTR_CASE => PDO::CASE_LOWER,
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
			PDO::ATTR_STRINGIFY_FETCHES => false,
			PDO::ATTR_EMULATE_PREPARES => false,
	);

	/**
	 * Establish a database connection.
	 *
	 * @param  array  $config
	 * @return PDO
	 */
	abstract public function connect(array $config);

	/**
	 * Get the PDO options based on the configuration.
	 *
	 * @param  array  $config
	 * @return array
	 */
	protected function getOptions(array $config)
	{
		$options = $config['options'];

		return array_diff_key($this->options, $options) + $options;
	}

	/**
	 * Create a new PDO connection.
	 *
	 * @param  string  $dsn
	 * @param  array   $config
	 * @param  array   $options
	 * @return PDO
	 */
	protected function createConnection($dsn, array $config, array $optinos)
	{
		return new PDO($dsn, $config['username'], $config['password'], $options);
	}

}