<?php

namespace App\Amavis;

class DeliveryStatus
{
    public const Pass = 'P';
    public const Reject = 'R';
    public const Bounce = 'B';
    public const Discard = 'D';
    public const TempFile = 'T';
}
