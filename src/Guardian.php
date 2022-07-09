<?php

namespace Jasny\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Apply guards for a controller.
 * Guard are attributes of the controller or method.
 */
class Guardian
{
    /**
     * Instantiate a guard from an attribute.
     */
    protected function instantiateGuard(\ReflectionAttribute $attribute): Guard
    {
        return $attribute->newInstance();
    }

    /**
     * Run a set of guards.
     */
    public function guard(
        \ReflectionObject|\ReflectionMethod $subject,
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ?ResponseInterface {
        $attributes = $subject->getAttributes(Guard::class, \ReflectionAttribute::IS_INSTANCEOF);

        foreach ($attributes as $attribute) {
            $guard = $this->instantiateGuard($attribute);
            $result = $guard($request, $response);

            if ($result !== null) {
                return $result;
            }
        }

        return null;
    }
}
