<?php

namespace Jasny\Controller\Parameter;

use Psr\Http\Message\ServerRequestInterface;

#[\Attribute]
class BodyParam implements Parameter
{
    use SingleParameter;

    /**
     * Get request body parameter.
     * If no key is specified return the parsed body and uploaded files or the body as string.
     *
     * Optionally apply filtering to the value.
     * @link http://php.net/manual/en/filter.filters.php
     */
    public function getValue(ServerRequestInterface $request, string $key, string $type, bool $required = false): mixed
    {
        $params = $request->getParsedBody();

        if ($required && !isset($params[$this->key])) {
            throw new InputException("Missing required body parameter '{$this->key}'");
        }

        return $this->filter($params[$this->key] ?? null, $type);
    }
}
