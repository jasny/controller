<?php
declare(strict_types=1);

namespace Jasny;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Execute controller on given route
 */
trait Routing
{
    /**
     * Get request, set for controller
     *
     * @return ServerRequestInterface
     */
    abstract public function getRequest(): ServerRequestInterface;

    /**
     * Get response, set for controller
     */
    abstract public function getResponse(): ResponseInterface;

    /**
     * Respond with 404 not found
     *
     * @param int $code  HTTP status code
     * @return $this
     */
    abstract public function notFound(int $code = 404): static;
    

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
     */
    protected function getActionMethod(string $action): string
    {
        $sentence = preg_replace('/[\W_]+/', ' ', $action);
        return lcfirst(str_replace(' ', '', ucwords($sentence))) . 'Action';
    }
    
    /**
     * Called before executing the action.
     * @codeCoverageIgnore
     * 
     * <code>
     * protected function before()
     * {
     *    if ($this->auth->getUser()->getCredits() <= 0) {
     *        return $this->paymentRequired()->output("Sorry, you're out of credits");
     *    }
     * }
     * </code>
     *
     * @return void|ResponseInterface|static
     */
    protected function before()
    {
    }
    
    /**
     * Called after executing the action.
     * @codeCoverageIgnore
     *
     * @return void|ResponseInterface|static
     */
    protected function after()
    {
    }

    /**
     * Run the controller
     */
    public function run(): ResponseInterface
    {
        $route = $this->getRoute();
        $method = $this->getActionMethod(isset($route->action) ? $route->action : 'default');

        if (!method_exists($this, $method)) {
            $this->notFound();
            return $this->getResponse();
        }

        $before = $this->before();
        
        if ($before !== null) {
            return $before instanceof ResponseInterface ? $before : $this->getResponse();
        }

        $args = $route->args ?? $this->getFunctionArgs($route, new \ReflectionMethod($this, $method));
        $result = [$this, $method]($args);

        $response = $this->after() ?? $result;

        return $response instanceof ResponseInterface ? $response : $this->getResponse();
    }

    /**
     * Get the arguments for a function from a route using reflection.
     */
    protected function getFunctionArgs(\stdClass $route, \ReflectionFunctionAbstract $refl): array
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

