<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Db\Adapter\Driver\Firebird;

use Zend\Db\Adapter\Driver\AbstractConnection;
use Zend\Db\Adapter\Exception;

class Connection extends AbstractConnection
{
    /**
     * @var Mysqli
     */
    protected $driver = null;

    /**
     * @var \mysqli
     */
    protected $resource = null;

    /**
     * Constructor
     *
     * @param  array|mysqli|null                                   $connectionInfo
     * @throws \Zend\Db\Adapter\Exception\InvalidArgumentException
     */
    public function __construct($connectionInfo = null)
    {
        if (is_array($connectionInfo)) {
            $this->setConnectionParameters($connectionInfo);
        } elseif (is_resource($connectionInfo)) {
            $this->setResource($connectionInfo);
        } elseif (null !== $connectionInfo) {
            throw new Exception\InvalidArgumentException('$connection must be an array of parameters, a firebird connection resource or null');
        }
    }

    /**
     * @param  Firebird $driver
     * @return self
     */
    public function setDriver(Firebird $driver)
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Set resource
     *
     * @param  resource $resource
     * @return self
     */
    public function setResource($resource)
    {
        $this->resource = $resource;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getCurrentSchema()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $info = ibase_server_info($this->resource);
die(var_dump($info));
        return (isset($info->DB_NAME) ? $info->DB_NAME : '');
    }
    
    /**
     * {@inheritDoc}
     */
    public function connect()
    {
        if (is_resource($this->resource)) {
            return $this;
        }

        // localize
        $p = $this->connectionParameters;

        // given a list of key names, test for existence in $p
        $findParameterValue = function (array $names) use ($p) {
            foreach ($names as $name) {
                if (isset($p[$name])) {
                    return $p[$name];
                }
            }

            return;
        };

        $hostname = $findParameterValue(array('hostname', 'host'));
        $username = $findParameterValue(array('username', 'user'));
        $password = $findParameterValue(array('password', 'passwd', 'pw'));
        $database = $findParameterValue(array('database', 'dbname', 'db'));
        $port     = (isset($p['port'])) ? (int) $p['port'] : null;
        $socket   = (isset($p['socket'])) ? $p['socket'] : null;

        $this->resource = ibase_connect($hostname . ':' . $database, $username, $password);
        

        if ($this->resource === false) {
            throw new Exception\RuntimeException(sprintf(
                '%s: Unable to connect to database',
                __METHOD__
            ));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function isConnected()
    {
        return ($this->resource instanceof \mysqli);
    }

    /**
     * {@inheritDoc}
     */
    public function disconnect()
    {
        if ($this->resource instanceof \mysqli) {
            $this->resource->close();
        }
        $this->resource = null;
    }

    /**
     * {@inheritDoc}
     */
    public function beginTransaction()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $this->resource->autocommit(false);
        $this->inTransaction = true;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function commit()
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        $this->resource->commit();
        $this->inTransaction = false;
        $this->resource->autocommit(true);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function rollback()
    {
        if (!$this->isConnected()) {
            throw new Exception\RuntimeException('Must be connected before you can rollback.');
        }

        if (!$this->inTransaction) {
            throw new Exception\RuntimeException('Must call beginTransaction() before you can rollback.');
        }

        $this->resource->rollback();
        $this->resource->autocommit(true);
        $this->inTransaction = false;

        return $this;
    }

    /**
     * {@inheritDoc}
     *
     * @throws Exception\InvalidQueryException
     */
    public function execute($sql)
    {
        if (!$this->isConnected()) {
            $this->connect();
        }

        if ($this->profiler) {
            $this->profiler->profilerStart($sql);
        }

        $resultResource = $this->resource->query($sql);

        if ($this->profiler) {
            $this->profiler->profilerFinish($sql);
        }

        // if the returnValue is something other than a mysqli_result, bypass wrapping it
        if ($resultResource === false) {
            throw new Exception\InvalidQueryException($this->resource->error);
        }

        $resultPrototype = $this->driver->createResult(($resultResource === true) ? $this->resource : $resultResource);

        return $resultPrototype;
    }

    /**
     * {@inheritDoc}
     */
    public function getLastGeneratedValue($name = null)
    {
        // todoreturn $this->resource->insert_id;
        return 0;
    }
}
