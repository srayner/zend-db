<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\Db\Adapter\Driver\Firebird;

use Iterator;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\Adapter\Exception;

class Result implements
    Iterator,
    ResultInterface
{
    /**
     * @var \mysqli|\mysqli_result|\mysqli_stmt
     */
    protected $resource = null;

    /**
     * @var bool
     */
    protected $isBuffered = null;

    /**
     * Cursor position
     * @var int
     */
    protected $position = 0;

    /**
     * Number of known rows
     * @var int
     */
    protected $numberOfRows = -1;

    /**
     * Is the current() operation already complete for this pointer position?
     * @var bool
     */
    protected $currentComplete = false;

    /**
     * @var bool
     */
    protected $nextComplete = false;

    /**
     * @var bool
     */
    protected $currentData = false;

    /**
     *
     * @var array
     */
    protected $statementBindValues = array('keys' => null, 'values' => array());

    /**
     * @var mixed
     */
    protected $generatedValue = null;

    /**
     * Initialize
     *
     * @param mixed $resource
     * @param mixed $generatedValue
     * @param bool|null $isBuffered
     * @throws Exception\InvalidArgumentException
     * @return Result
     */
    public function initialize($resource, $generatedValue)
    {
        $this->resource = $resource;
        $this->generatedValue = $generatedValue;
        return $this;
    }

    /**
     * Return the resource
     *
     * @return mixed
     */
    public function getResource()
    {
        return $this->resource;
    }

    /**
     * Force buffering
     *
     * @return void
     */
    public function buffer()
    {
        return;
    }
    
    /**
     * Check if is buffered
     *
     * @return bool|null
     */
    public function isBuffered()
    {
        return false;
    }
    
    /**
     * Is query result?
     *
     * @return bool
     */
    public function isQueryResult()
    {
        return (ibase_num_fields($this->resource) > 0);
    }

    /**
     * Get affected rows
     *
     * @return int
     */
    public function getAffectedRows()
    {
        if ($this->resource instanceof \mysqli || $this->resource instanceof \mysqli_stmt) {
            return $this->resource->affected_rows;
        }

        return $this->resource->num_rows;
    }

    /**
     * Current
     *
     * @return mixed
     */
    public function current()
    {
        if ($this->currentComplete) {
            return $this->currentData;
        }

        $this->currentData = ibase_fetch_assoc($this->resource);
        return $this->currentData;
    }

    /**
     * Next
     *
     * @return void
     */
    public function next()
    {
        $this->currentData = ibase_fetch_assoc($this->resource);
        $this->currentComplete = true;
        $this->position++;
        return $this->currentData;
    }

    /**
     * Key
     *
     * @return mixed
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * Rewind
     *
     * @throws Exception\RuntimeException
     * @return void
     */
    public function rewind()
    {
        if ($this->position > 0) {
            throw new Exception\RuntimeException(
                'This result is a forward only result set, calling rewind() after moving forward is not supported'
            );
        }
        $this->currentData = ibase_fetch_assoc($this->resource);
        $this->currentComplete = true;
        $this->position = 1;
    }

    /**
     * Valid
     *
     * @return bool
     */
    public function valid()
    {
        return ($this->currentData !== false);
    }

    /**
     * Count
     *
     * @throws Exception\RuntimeException
     * @return int
     */
    public function count()
    {
        return;
    }

    /**
     * Get field count
     *
     * @return int
     */
    public function getFieldCount()
    {
        return ibase_num_fields($this->resource);
    }

    /**
     * Get generated value
     *
     * @return mixed|null
     */
    public function getGeneratedValue()
    {
        return $this->generatedValue;
    }
}
