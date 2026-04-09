<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model\Enums;

enum RideStatus: int
{
    case DRAFT = 1;
    case PENDING = 2;
    case ACCEPTED = 3;
    case IN_PROGRESS = 4;
    case COMPLETED = 5;
    case CANCELLED = 6;
}
