<?php

namespace Jasny\Controller\Parameter;

use Psr\Http\Message\ServerRequestInterface;

#[\Attribute]
class Query implements Parameter
{
    /**
     * Get a query parameter.
     */
    public function getValue(ServerRequestInterface $request, string $name, string $type, bool $required = false): mixed
    {
        return $request->getQueryParams();
    }
}
