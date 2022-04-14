<?php

namespace Jasny\Traits;

/**
 * Class for testing 'RouteAction' trait
 */
abstract class RouteActionController
{
    use \Jasny\Routing;
    
    public function defaultAction($foo, $bar = null)
    {
    }
    
    public function runTestAction()
    {
    }
}
