<?php

namespace Jasny\Controller\Parameter;

use Jasny\Controller\ParameterException;
use Psr\Http\Message\ServerRequestInterface;

#[\Attribute]
class Cookie extends SingleParameter
{
    /**
     * Get a cookie parameter.
     */
    public function getValue(
        ServerRequestInterface $request,
        string $name,
        ?string $type,
        bool $required = false
    ): mixed {
        $key = $this->key ?? $name;
        $params = $request->getCookieParams();

        if ($required && !isset($params[$key])) {
            throw new ParameterException("Missing required cookie parameter '$key'");
        }

        return $this->filter($params[$key] ?? null, $type);
    }
}
