<?php

namespace genaside\PHPTorrent\Structures;

/**
 * File Infomation List
 */
class FileInformationList implements IteratorAggregate, ArrayAccess, Countable, JsonSerializable
{

    private $container = array();
    private $count = 0;

    public function __construct()
    {
    }

    // implement functions

    public function count()
    {
        return $this->count;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->container);
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    public function jsonSerialize()
    {
        return $this->container;
    }


    // My functions


    public function add(FileInformation $value)
    {
        $this->container[$this->count++] = $value;
    }


    public function getTotalFileSize()
    {
        $total_size = 0;
        foreach ($this->container as $element) {
            $total_size += $element->size;
        }
        return $total_size;
    }


}