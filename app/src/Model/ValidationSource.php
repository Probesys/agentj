<?php

namespace App\Model;

use App\Util\Enum;

// TODO: singulariser ?
class ValidationSource extends Enum
{
    public const user = 0;
    public const captcha = 1;
    public const group = 2;
    public const admin = 3;
    public const outmail = 4;
}

