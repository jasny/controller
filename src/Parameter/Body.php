<?php

namespace Jasny\Controller\Parameter;

use Psr\Http\Message\ServerRequestInterface;

#[\Attribute]
class Body implements Parameter
{
    /**
     * Get body or body parameters.
     */
    public function getValue(ServerRequestInterface $request, string $name, ?string $type, bool $required = false): mixed
    {
        if ($type === 'string') {
            return (string)$request->getBody();
        }

        $data = $request->getParsedBody();

        if (
            is_array($data) &&
            str_starts_with(strtolower($request->getHeaderLine('Content-Type')), 'multipart/form-data')
        ) {
            $files = $request->getUploadedFiles();
            $data = array_replace_recursive($data, $files);
        }

        return $data;
    }
}
