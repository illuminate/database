<?php namespace Illuminate\Database\Connectors;

use PDO;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SqlServerConnection;

class ConnectionFactory {

	/**
	 * Establish a PDO connection based on the configuration.
	 *
	 * @param  array  $config
	 * @return Illuminate\Database\Connection
	 */
	public function make(array $config)
	{
		$pdo = $this->createConnector($config)->connect($config);

		$connection = $this->createConnection($config['driver'], $pdo);

		$connection->setTablePrefix($config['prefix']);

		return $connection;
	}

	/**
	 * Create a connector instance based on the configuration.
	 *
	 * @param  array  $config
	 * @return Illuminate\Database\Connectors\ConnectorInterface
	 */
	public function createConnector(array $config)
	{
		if ( ! isset($config['driver']))
		{
			throw new \InvalidArgumentException("A driver must be specified.");
		}

		switch ($config['driver'])
		{
			case 'mysql':
				return new MySqlConnector;

			case 'pgsql':
				return new PostgresConnector;

			case 'sqlite':
				return new SQLiteConnector;

			case 'sqlsrv':
				return new SqlServerConnector;
		}

		throw new \InvalidArgumentException("Unsupported driver [{$config['driver']}");
	}

	/**
	 * Create a new connection instance.
	 *
	 * @param  string  $driver
	 * @param  PDO     $connection
	 * @return Illuminate\Database\Connection
	 */
	protected function createConnection($driver, PDO $connection)
	{
		switch ($driver)
		{
			case 'mysql':
				return new MySqlConnection($connection);

			case 'pgsql':
				return new PostgresConnection($connection);

			case 'sqlite':
				return new SQLiteConnection($connection);

			case 'sqlsrv':
				return new SqlServerConnection($connection);
		}

		throw new \InvalidArgumentException("Unsupported driver [$driver]");
	}

}