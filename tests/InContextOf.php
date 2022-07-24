<?php

namespace Jasny\Test\Controller;

trait InContextOf
{
    public function inContextOf(object $object, \Closure $function) {
        return $function->call($object);
    }
}
