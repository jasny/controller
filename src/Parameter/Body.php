<?php

namespace Jasny\Controller\Parameter;

use Psr\Http\Message\ServerRequestInterface;

#[\Attribute]
class Body implements Parameter
{
    /**
     * Get body or body parameters.
     */
    public function getValue(
        ServerRequestInterface $request,
        string $name,
        ?string $type,
        bool $required = false
    ): mixed {
        return $type === 'string'
            ? (string)$request->getBody()
            : $request->getParsedBody();
    }
}
