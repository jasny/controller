<?php

namespace Jasny\Controller\Traits;

use Jasny\Controller\Guardian;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

trait Guarded
{
    protected Guardian $guardian;

    abstract protected function getRequest(): ServerRequestInterface;
    abstract protected function getResponse(): ResponseInterface;

    private function getGuardian(): Guardian
    {
        $this->guardian ??= new Guardian();
        return $this->guardian;
    }

    protected function guard(\ReflectionObject|\ReflectionMethod $subject): ?ResponseInterface
    {
        return $this->getGuardian()->guard($subject, $this->getRequest(), $this->getResponse());
    }
}
