<?php

namespace App\Enums;

/** How an artist works in a studio. */
enum PresenceType: string
{
    case Resident = 'resident';
    case Guest = 'guest';
}
