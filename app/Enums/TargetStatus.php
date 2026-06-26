<?php

namespace App\Enums;

enum TargetStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Achieved = 'achieved';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
}