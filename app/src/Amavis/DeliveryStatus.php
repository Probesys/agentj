<?php

namespace App\Amavis;

class DeliveryStatus
{
    public const PASS = 'P';
    public const REJECT = 'R';
    public const BOUNCE = 'B';
    public const DISCARD = 'D';
    public const TEMP_FILE = 'T';
}
