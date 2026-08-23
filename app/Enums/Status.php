<?php

namespace App\Enums;

enum Status: string
{
    case Open = 'open';
    case Pending = 'pending';
    case Closed = 'closed';
}
