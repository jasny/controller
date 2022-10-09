<?php

namespace Jasny\Controller\Parameter;

use Jasny\Controller\ParameterException;
use Psr\Http\Message\ServerRequestInterface;

#[\Attribute]
class Attr extends SingleParameter
{
    /**
     * Get a request attribute.
     */
    public function getValue(
        ServerRequestInterface $request,
        string $name,
        ?string $type,
        bool $required = false
    ): mixed {
        $key = $this->key ?? $name;
        $value = $request->getAttribute($key);

        if ($required && $value === null) {
            throw new ParameterException("Missing required request attribute '$key'");
        }

        return $this->filter($value, $type);
    }
}
