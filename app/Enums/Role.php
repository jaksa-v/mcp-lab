<?php

namespace App\Enums;

enum Role: string
{
    case Requester = 'requester';
    case Agent = 'agent';
    case Admin = 'admin';

    public function isStaff(): bool
    {
        return $this !== self::Requester;
    }
}
