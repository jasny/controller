<?php

namespace Jasny\Controller\Traits;

/**
 * Class for testing 'RouteAction' trait
 */
abstract class RouteActionController
{
    use Jasny\Controller\Controller\Routing;
    
    public function defaultAction($foo, $bar = null)
    {
    }
    
    public function runTestAction()
    {
    }
}
