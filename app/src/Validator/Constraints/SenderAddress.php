<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class SenderAddress extends Constraint
{
    public string $message = 'sender_address.invalid';
}
