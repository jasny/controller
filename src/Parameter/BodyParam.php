<?php

namespace Jasny\Controller\Parameter;

use Jasny\Controller\ParameterException;
use Psr\Http\Message\ServerRequestInterface;

#[\Attribute]
class BodyParam extends SingleParameter
{
    /**
     * Get request body parameter.
     * If no key is specified return the parsed body and uploaded files or the body as string.
     *
     * Optionally apply filtering to the value.
     * @link http://php.net/manual/en/filter.filters.php
     */
    public function getValue(ServerRequestInterface $request, string $name, string $type, bool $required = false): mixed
    {
        $key = $this->key ?? $name;
        $params = $request->getParsedBody();

        if ($required && !isset($params[$key])) {
            throw new ParameterException("Missing required body parameter '$key'");
        }

        return $this->filter($params[$key] ?? null, $type);
    }
}
