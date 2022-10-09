<?php

namespace Jasny\Controller\Parameter;

use Psr\Http\Message\ServerRequestInterface;

interface Parameter
{
    /**
     * Get the value for the attribute from the request.
     */
    public function getValue(
        ServerRequestInterface $request,
        string $name,
        string $type,
        bool $required = false
    ): mixed;
}