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
     * @return Controller|\PHPUnit_Framework_MockObject_MockObject
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
}
