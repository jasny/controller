<?php
declare(strict_types=1);

namespace Jasny\Controller;

use Jasny\Controller\Parameter\Parameter;
use Jasny\Controller\Parameter\Path;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller base class
 */
abstract class Controller
{
    use Traits\Header,
        Traits\Output,
        Traits\CheckRequest,
        Traits\CheckResponse;
    
    private ServerRequestInterface $request;
    private ResponseInterface $response;

    
    /**
     * Get request, set for controller
     */
    protected function getRequest(): ServerRequestInterface
    {
        if (!isset($this->request)) {
            throw new \LogicException("Request not set, the controller has not been invoked");
        }
        
        return $this->request;
    }

    /**
     * Get response, set for controller
     */
    protected function getResponse(): ResponseInterface
    {
        if (!isset($this->response)) {
            throw new \LogicException("Response not set, the controller has not been invoked");
        }
        
        return $this->response;
    }

    /**
     * Set the response
     */
    protected function setResponse(ResponseInterface $response): void
    {
        $this->response = $response;
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
     * Get the method name of the action
     */
    protected function getActionMethod(string $action): string
    {
        $sentence = preg_replace('/[\W_]+/', ' ', $action);
        return lcfirst(str_replace(' ', '', ucwords($sentence)));
    }

    /**
     * Get the arguments for a function from a route using reflection.
     */
    protected function getFunctionArgs(\ReflectionFunctionAbstract $refl): array
    {
        $args = [];

        foreach ($refl->getParameters() as $param) {
            $attribute = $param->getAttributes(Parameter::class)[0] ?? new Path();

            $args[] = $attribute->getValue(
                $this->request,
                $param->getName(),
                $param->getType()->getName(),
                !$param->isOptional(),
            ) ?? $param->getDefaultValue();
        }

        return $args;
    }

    /**
     * Invoke the controller.
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->request = $request;
        $this->response = $response;

        $method = $this->getActionMethod($request->getAttribute('route:action', 'run'));
        $args = $args ?? $this->getFunctionArgs(new \ReflectionMethod($this, $method));

        if (!method_exists($this, $method)) {
            return $this->notFound()->getResponse();
        }

        $before = $this->before();

        if ($before !== null) {
            return $before instanceof ResponseInterface ? $before : $this->getResponse();
        }

        $result = [$this, $method](...$args);

        $response = $this->after() ?? $result;

        return $response instanceof ResponseInterface ? $response : $this->getResponse();
    }
}
