<?php

namespace Jasny\Test\Controller\Guard;

use Jasny\Controller\Guard;

#[\Attribute]
class PaymentRequired extends Guard
{
    public function process()
    {
        return $this->paymentRequired();
    }
}
