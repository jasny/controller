<?php

namespace Jasny\Controller;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Execute controller on given route
 */
trait RouteAction
{
    /**
     * Get request, set for controller
     *
     * @return ServerRequestInterface
     */
     abstract public function getRequest();

     /**
     * Get response. set for controller
     *
     * @return ResponseInterface
     */
     abstract public function getResponse();

    /**
     * Run the controller
     *
     * @return ResponseInterface
     */
    public function run() {
        $request = $this->getRequest();
        $route = $request->getAttribute('route');
        $method = $this->getActionMethod(isset($route->action) ? $route->action : 'default');
        
        if (!method_exists($this, $method)) {
            return $this->setResponseError(404, 'Not Found');
        }

        try {
            $args = isset($route->args) ? 
                $route->args :
                $this->getFunctionArgs($route, new \ReflectionMethod($this, $method));            
        } catch (\RuntimeException $e) {
            return $this->setResponseError(400, 'Bad Request');   
        }

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
                        ? $refl->class . ':' . $refl->name
                        : $refl->name;

                    throw new \RuntimeException("Missing argument '$key' for $fn()");
                }
                
                $value = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            }

            $args[$key] = $value;
        }
        
        return $args;
    }

    /**
     * Set response to error state
     *
     * @param int $code
     * @param string $message 
     * @return ResponseInterface
     */
    protected function setResponseError($code, $message)
    {
        $response = $this->getResponse();

        $errorResponse = $response->withStatus($code);
        $errorResponse->getBody()->write($message);

        return $errorResponse;
    }
}

