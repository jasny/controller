<?php

namespace Jasny\Controller;

use Jasny\Controller;

/**
 * Additional test methods
 */
trait TestHelper
{
    /**
     * Get mock for controller
     *
     * @param array $methods  Methods to mock
     * @return Controller|\PHPUnit_Framework_MockObject_MockObject
     */
    public function getController($methods = [])
    {
        $builder = $this->getMockBuilder(Controller::class)->disableOriginalConstructor();
        if ($methods) {
            $builder->setMethods($methods);
        }

        return $builder->getMockForAbstractClass();
    }
}
