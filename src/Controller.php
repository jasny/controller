<?php
declare(strict_types=1);

namespace Jasny\Controller;

use Jasny\Controller\Parameter\Parameter;
use Jasny\Controller\Parameter\PathParam;
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
            throw new \LogicException("Request not set, the controller has not been invoked"); // @codeCoverageIgnore
        }
        
        return $this->request;
    }

    /**
     * Get response, set for controller
     */
    protected function getResponse(): ResponseInterface
    {
        if (!isset($this->response)) {
            throw new \LogicException("Response not set, the controller has not been invoked"); // @codeCoverageIgnore
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
            [$argRefl] = $param->getAttributes(
                Parameter::class,
                \ReflectionAttribute::IS_INSTANCEOF
            ) + [null];
            $attribute = $argRefl?->newInstance() ?? new PathParam();

            $args[] = $attribute->getValue(
                $this->request,
                $param->getName(),
                $param->getType()?->getName(),
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

        $method = $this->getActionMethod($request->getAttribute('route:action', 'process'));
        $refl = method_exists($this, $method) ? new \ReflectionMethod($this, $method) : null;

        if ($refl === null || !$refl->isPublic() || $refl->isConstructor() || $method === __METHOD__) {
            return $this->notFound()->output('Not found')->getResponse();
        }

        try {
            $args = $this->getFunctionArgs($refl);
        } catch (ParameterException $exception) {
            return $this->badRequest()->output($exception->getMessage())->getResponse();
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
