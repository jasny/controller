<?php

namespace Jasny\Controller\Parameter;

use Jasny\Controller\ParameterException;
use Psr\Http\Message\ServerRequestInterface;

#[\Attribute]
class UploadedFile extends SingleParameter
{
    /**
     * Get uploaded file from request.
     */
    public function getValue(ServerRequestInterface $request, string $name, ?string $type, bool $required = false): mixed
    {
        $key = $this->key ?? $name;
        $params = $request->getUploadedFiles();

        if ($required && !isset($params[$key])) {
            throw new ParameterException("Missing required uploaded file '$key'");
        }

        return $params[$key] ?? null;
    }
}
