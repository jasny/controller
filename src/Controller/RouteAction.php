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
     * @var boolean
     */
    protected $actionCancelled = false;
    
    
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
     * Respond with a server error
     *
     * @param string $message
     * @param int    $code     HTTP status code
     */
    abstract public function notFound($message = '', $code = 404);
    

    /**
     * Get the route
     * 
     * @return \stdClass
     */
    protected function getRoute()
    {
        $route = $this->getRequest()->getAttribute('route');
        
        if (!isset($route)) {
            throw new \LogicException("Route has not been set");
        }
        
        if (is_array($route)) {
            $route = (object)$route;
        }
        
        if (!$route instanceof \stdClass) {
            $type = (is_object($route) ? get_class($route) . ' ' : '') . gettype($route);
            throw new \UnexpectedValueException("Expected route to be a stdClass object, not a $type");
        }
        
        return $route;
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
     * Called before executing the action.
     * 
     * <code>
     * protected function beforeAction()
     * {
     *    $this->respondWith('json'); // Respond with JSON by default
     * 
     *    if ($this->auth->getUser()->getCredits() <= 0) {
     *        $this->paymentRequired();
     *    }
     * }
     * </code>
     */
    protected function before()
    {
    }
    
    /**
     * Called before executing the action.
     */
    protected function after()
    {
    }
    
    /**
     * Cancel the action
     * 
     * @return boolean
     */
    public function cancel()
    {
        $this->actionCancelled = true;
    }

    /**
     * Check if the action is cancelled
     * 
     * @return boolean
     */
    public function isCancelled()
    {
        return $this->actionCancelled;
    }
    
    /**
     * Run the controller
     *
     * @return ResponseInterface
     */
    public function run()
    {
        $route = $this->getRoute();
        $method = $this->getActionMethod(isset($route->action) ? $route->action : 'default');

        if (!method_exists($this, $method)) {
            return $this->notFound();
        }

        $this->before();
        
        if (!$this->isCancelled()) {
            $args = isset($route->args) ? $route->args
                : $this->getFunctionArgs($route, new \ReflectionMethod($this, $method)); 

            call_user_func_array([$this, $method], $args);
        }
        
        $this->after();
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
                $value = $route->$key;
            } else {
                if (!$param->isOptional()) {
                    $fn = $refl instanceof \ReflectionMethod ? $refl->class . '::' . $refl->name : $refl->name;
                    throw new \RuntimeException("Missing argument '$key' for {$fn}()");
                }
                
                $value = $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null;
            }

            $args[$key] = $value;
        }
        
        return $args;
    }
}

