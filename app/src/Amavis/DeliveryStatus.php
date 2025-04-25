<?php

namespace App\Amavis;

enum DeliveryStatus: string
{
    case Pass = 'P';
    case Reject = 'R';
    case Bounce = 'B';
    case Discard = 'D';
    case TempFile = 'T';
}
