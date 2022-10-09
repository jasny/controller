<?php

namespace Jasny\Controller\Parameter;

use Psr\Http\Message\ServerRequestInterface;

#[\Attribute]
class Cookies implements Parameter
{
    /**
     * Get a cookies.
     *
     * @return array<string,mixed>
     */
    public function getValue(
        ServerRequestInterface $request,
        string $name,
        ?string $type,
        bool $required = false
    ): array {
        return $request->getCookieParams();
    }
}
