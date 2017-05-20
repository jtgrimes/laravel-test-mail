<?php namespace Tests;

use ReflectionClass;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * Gets a private method
     *
     * @param $name
     *
     * @return ReflectionMethod
     */
    protected static function getPrivateMethod($class, $name)
    {
        $class = new ReflectionClass($class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }

    public function tearDown()
    {
        \Mockery::close();
    }
}
