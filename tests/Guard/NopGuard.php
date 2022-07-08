<?php

namespace Jasny\Test\Controller\Guard;

use Jasny\Controller\Guard;

#[\Attribute]
class NopGuard extends Guard
{
    public function process()
    {
    }
}
