<?php

namespace Jasny\Controller\Parameter;

use Psr\Http\Message\ServerRequestInterface;

#[\Attribute]
class Query implements Parameter
{
    /**
     * Get all query parameters.
     *
     * @return array<string,mixed>
     */
    public function getValue(
        ServerRequestInterface $request,
        string $name,
        ?string $type,
        bool $required = false
    ): array {
        return $request->getQueryParams();
    }
}
