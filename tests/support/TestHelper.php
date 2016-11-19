<?php

namespace Jasny\Controller;

use Jasny\Controller;

/**
 * Additional test methods
 */
trait TestHelper
{
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
    public function getController($methods = [])
    {
        $builder = $this->getMockBuilder($this->getControllerClass())->disableOriginalConstructor();
        if ($methods) {
            $builder->setMethods($methods);
        }

        return $builder->getMockForAbstractClass();
    }
}
