<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
final class Altcha extends Constraint
{
    /**
     * @var string
     */
    public $message = 'invalid_altcha';
}
