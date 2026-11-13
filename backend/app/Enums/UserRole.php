<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case Artist = 'artist';
    case Admin = 'admin';
}
