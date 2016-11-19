<?php

namespace Jasny\Controller;

use Jasny\Controller;

/**
 * Class for testing 'RouteAction' trait
 */
abstract class RouteActionController
{
    use Controller\RouteAction;
    
    public function defaultAction($foo, $bar = null)
    {
    }
    
    public function runTestAction()
    {
    }
}
