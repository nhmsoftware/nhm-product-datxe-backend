<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\DTO;

use Illuminate\Http\Request;
use Carbon\Carbon;

final class DashboardReportFilterDTO
{
    public function __construct(
        public readonly Carbon $startDate,
        public readonly Carbon $endDate,
        public readonly string $interval = 'day',
        public readonly ?string $area = null,
        public readonly ?int $driverGroupType = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $startDate = $request->input('start_date') 
            ? Carbon::parse($request->input('start_date'))->startOfDay() 
            : now()->subDays(7)->startOfDay();
            
        $endDate = $request->input('end_date') 
            ? Carbon::parse($request->input('end_date'))->endOfDay() 
            : now()->endOfDay();

        return new self(
            startDate: $startDate,
            endDate: $endDate,
            interval: $request->input('interval', 'day'),
            area: $request->input('area'),
            driverGroupType: $request->has('driver_group_type') ? (int) $request->input('driver_group_type') : null,
        );
    }
}
