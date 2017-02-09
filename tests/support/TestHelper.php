<?php

namespace Jasny\Controller;

use Jasny\Controller;
use Jasny\TestHelper as Base;
use PHPUnit_Framework_MockObject_MockObject as MockObject;

/**
 * Additional test methods
 */
trait TestHelper
{
    use Base;

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
     * @return Controller|Controller\Session|Controller\View|MockObject
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
