<?php

namespace Tinyga\Model;

use ReflectionClass;

abstract class BaseModel
{
    /**
     * BaseModel constructor.
     *
     * @param array $values
     */
    public function __construct($values = [])
    {
        foreach ($values as $key => $value) {
            $this->{$key} = $value;
        }
    }


    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * @return array
     */
    public function toArray() {
        /** @noinspection PhpUnhandledExceptionInspection */
        $reflectionClass = new ReflectionClass($this);
        $array = array();
        foreach ($reflectionClass->getProperties() as $property) {
            $property->setAccessible(true);
            $array[$property->getName()] = $property->getValue($this);
            $property->setAccessible(false);
        }
        return $array;
    }
}
