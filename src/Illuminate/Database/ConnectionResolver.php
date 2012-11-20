<?php namespace Illuminate\Database;

class ConnectionResolver implements ConnectionResolverInterface {

	/**
	 * All of the registered connections.
	 *
	 * @var array
	 */
	protected $connections = array();

	/**
	 * The default connection name.
	 *
	 * @var string
	 */
	protected $default;

	/**
	 * Create a new connection resolver instance.
	 *
	 * @param  array  $connections
	 * @return void
	 */
	public function __construct(array $connections = array())
	{
		foreach ($connections as $name => $connection)
		{
			$this->addConnection($name, $connection);
		}
	}

	/**
	 * Get a database connection instance.
	 *
	 * @param  string  $name
	 * @return Illuminate\Database\Connection
	 */
	public function connection($name = null)
	{
		return $this->connections[$name];
	}

	/**
	 * Add a connection to the resolver.
	 *
	 * @param  string  $name
	 * @param  Illuminate\Database\Connection  $connection
	 * @return void
	 */
	public function addConnection($name, Connection $connection)
	{
		$this->connections[$name] = $connection;
	}

	/**
	 * Get the default connection name.
	 *
	 * @return string
	 */
	public function getDefaultConnection()
	{
		return $this->default;
	}

	/**
	 * Set the default connection name.
	 *
	 * @param  string  $name
	 * @return void
	 */
	public function setDefaultConnection($name)
	{
		$this->default = $name;
	}

}