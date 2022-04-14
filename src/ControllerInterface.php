<?php
declare(strict_types=1);

namespace Jasny;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface for controllers
 */
interface ControllerInterface
{
    /**
     * Run the controller as function
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface      $response
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response);
}
