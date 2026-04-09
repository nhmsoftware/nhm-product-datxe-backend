<?php

declare(strict_types=1);

namespace App\Modules\Ride\Model\Enums;

enum VehicleType: int
{
    case BIKE = 1;
    case CAR_4_SEATS = 2;
    case CAR_7_SEATS = 3;
    case CAR_9_SEATS = 4;
}
