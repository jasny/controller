<?php

namespace Jasny\Controller;

use Jasny\Controller;

/**
 * Additional test methods
 */
trait TestHelper
{
    /**
     * Returns a builder object to create mock objects using a fluent interface.
     *
     * @param string $className
     *
     * @return \PHPUnit_Framework_MockObject_MockBuilder
     */
    abstract public function getMockBuilder($className);

    /**
     * Get the controller class
     *
     * @return string
     */
    protected function getControllerClass()
    {
        return Controller::class;
    }

    /**
     * Get mock for controller
     *
     * @param array $methods  Methods to mock
     * @return Controller|Controller\Session|Controller\View\Twig|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getController($methods = [], $mockClassName = null)
    {
        $class = $this->getControllerClass();

        $builder = $this->getMockBuilder($class)->disableOriginalConstructor();
        if ($methods) {
            $builder->setMethods($methods);
        }

        if (isset($mockClassName)) {
            $builder->setMockClassName($mockClassName);
        }

        $getMock = trait_exists($class) ? 'getMockForTrait' : 'getMockForAbstractClass';
        return $builder->$getMock();
    }

    /**
     * Set a private or protected property of the given object
     *
     * @param object $object
     * @param string $property
     * @param mixed  $value
     */
    protected function setPrivateProperty($object, $property, $value)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException("Excpected an object, got a " . gettype($object));
        }

        $refl = new \ReflectionProperty($object, $property);
        $refl->setAccessible(true);
        $refl->setValue($object, $value);
    }

    /**
     * Call protected method on some object
     *
     * @param object $object
     * @param string $name   Method name
     * @param array $args
     * @return mixed         Result of method call
     */
    protected function callProtectedMethod($object, $name, $args)
    {
        $class = new \ReflectionClass($object);
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $args);
    }
}
