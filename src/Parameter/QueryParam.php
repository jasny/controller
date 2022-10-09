<?php

namespace Jasny\Controller\Parameter;

use Jasny\Controller\ParameterException;
use Psr\Http\Message\ServerRequestInterface;

#[\Attribute]
class QueryParam extends SingleParameter
{
    /**
     * Get a query parameter.
     */
    public function getValue(
        ServerRequestInterface $request,
        string $name,
        ?string $type,
        bool $required = false
    ): mixed {
        $key = $this->key ?? str_replace('_', '-', $name);
        $params = $request->getQueryParams();

        if ($required && !isset($params[$key])) {
            throw new ParameterException("Missing required query parameter '$key'");
        }

        return $this->filter($params[$key] ?? null, $type);
    }
}
