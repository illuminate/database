<?php

namespace Illuminate\Database\Connectors;

use PDO;

class SqlServerConnector extends Connector implements ConnectorInterface
{
    /**
     * The PDO connection options.
     *
     * @var array
     */
    protected $options = [
        PDO::ATTR_CASE => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_ORACLE_NULLS => PDO::NULL_NATURAL,
        PDO::ATTR_STRINGIFY_FETCHES => false,
    ];

    /**
     * Establish a database connection.
     *
     * @param  array  $config
     * @return \PDO
     */
    public function connect(array $config)
    {
        $options = $this->getOptions($config);

        return $this->createConnection($this->getDsn($config), $config, $options);
    }

    /**
     * Create a DSN string from a configuration.
     *
     * @param  array   $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        // First we will create the basic DSN setup as well as the port if it is in
        // in the configuration options. This will give us the basic DSN we will
        // need to establish the PDO connections and return them back for use.
        if (in_array('dblib', $this->getAvailableDrivers())) {
            return $this->getDblibDsn($config);
        } else {
            return $this->getSqlSrvDsn($config);
        }
    }

    /**
     * Get the DSN string for a DbLib connection.
     *
     * @param  array  $config
     * @return string
     */
    protected function getDblibDsn(array $config)
    {
        $arguments = [
            'host' => $this->buildHostString($config, ':'),
            'dbname' => $config['database'],
        ];

        $arguments = array_merge(
            $arguments, array_only($config, ['appname', 'charset'])
        );

        return $this->buildConnectString('dblib', $arguments);
    }

    /**
     * Get the DSN string for a SqlSrv connection.
     *
     * @param  array  $config
     * @return string
     */
    protected function getSqlSrvDsn(array $config)
    {
        $arguments = [
            'Server' => $this->buildHostString($config, ','),
        ];

        if (isset($config['database'])) {
            $arguments['Database'] = $config['database'];
        }

        if (isset($config['appname'])) {
            $arguments['APP'] = $config['appname'];
        }

        return $this->buildConnectString('sqlsrv', $arguments);
    }

    /**
     * Build a connection string from the given arguments.
     *
     * @param  string  $driver
     * @param  array  $arguments
     * @return string
     */
    protected function buildConnectString($driver, array $arguments)
    {
        $options = array_map(function ($key) use ($arguments) {
            return sprintf('%s=%s', $key, $arguments[$key]);
        }, array_keys($arguments));

        return $driver.':'.implode(';', $options);
    }

    /**
     * Build a host string from the given configuration.
     *
     * @param  array  $config
     * @param  string  $separator
     * @return string
     */
    protected function buildHostString(array $config, $separator)
    {
        if (isset($config['port'])) {
            return $config['host'].$separator.$config['port'];
        } else {
            return $config['host'];
        }
    }

    /**
     * Get the available PDO drivers.
     *
     * @return array
     */
    protected function getAvailableDrivers()
    {
        return PDO::getAvailableDrivers();
    }
}
