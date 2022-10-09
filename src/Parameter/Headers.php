<?php

namespace Jasny\Controller\Parameter;

use Psr\Http\Message\ServerRequestInterface;

#[\Attribute]
class Headers implements Parameter
{
    /**
     * Get all request headers.
     *
     * @return array<string,string>
     */
    public function getValue(
        ServerRequestInterface $request,
        string $name,
        ?string $type,
        bool $required = false
    ): array {
        return array_map(static fn ($h) => implode(', ', $h), $request->getHeaders());
    }
}
