<?php
declare(strict_types=1);

namespace Jasny\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Controller base class
 */
abstract class Controller
{
    use Traits\Base,
        Traits\Header,
        Traits\Output,
        Traits\CheckRequest,
        Traits\CheckResponse,
        Traits\Guarded;

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

        $result = $this->guard(new \ReflectionObject($this));
        if ($result !== null) {
            return $result;
        }

        $result = $this->before();
        if ($result !== null) {
            return $result instanceof ResponseInterface ? $result : $this->getResponse();
        }

        $result = $this->guard($refl);
        if ($result !== null) {
            return $result;
        }

        $result = [$this, $method](...$args);

        $response = $this->after() ?? $result;

        return $response instanceof ResponseInterface ? $response : $this->getResponse();
    }
}
