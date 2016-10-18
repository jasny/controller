<?php

namespace Jasny\Controller;

use Psr\Http\Message\ResponseInterface;

/**
 * Execute controller on given route
 */
trait RouteAction
{
    /**
     * Run the controller
     *
     * @return ResponseInterface
     */
    public function run() {
        $request = $this->getRequest();
        if (!$request) {
            throw new \RuntimeException("Request object is not set for controller");            
        }

        $route = $request->getAttribute('route');
        $method = $this->getActionMethod(isset($route->action) ? $route->action : null);
        
        if (!method_exists($this, $method)) {
            throw new \RuntimeException("No method $method in conrtoller to route to");
        }

        $args = isset($route->args) ? 
            $route->args :
            $this->getFunctionArgs($route, new \ReflectionMethod($this, $method));

        $response = call_user_func_array([$this, $method], $args);

        return $response ?: $this->getResponse();        
    }

    /**
     * Get the method name of the action
     * 
     * @param string $action
     * @return string
     */
    protected function getActionMethod($action)
    {
        return \Jasny\camelcase($action) . 'Action';
    }

    /**
     * Get the arguments for a function from a route using reflection
     * 
     * @param object $route
     * @param \ReflectionFunctionAbstract $refl
     * @return array
     */
    protected function getFunctionArgs($route, \ReflectionFunctionAbstract $refl)
    {
        $args = [];
        $params = $refl->getParameters();

        foreach ($params as $param) {
            $key = $param->name;

            if (property_exists($route, $key)) {
                $value = $route->{$key};
            } else {
                if (!$param->isOptional()) {
                    $fn = $refl instanceof \ReflectionMethod
                        ? $refl->getDeclaringClass()->getName() . ':' . $refl->getName()
                        : $refl->getName();

                    throw new \RuntimeException("Missing argument '$key' for $fn()");
                }
                
                $value = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            }

            $args[$key] = $value;
        }
        
        return $args;
    }
}

