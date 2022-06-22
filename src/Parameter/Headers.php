<?php

namespace Jasny\Controller\Parameter;

use Psr\Http\Message\ServerRequestInterface;

#[\Attribute]
class Headers implements Parameter
{
    /**
     * Get all request headers.
     */
    public function getValue(ServerRequestInterface $request, string $name, string $type, bool $required = false): mixed
    {
        return array_map(fn ($h) => join(',', $h), $request->getHeaders());
    }
}
