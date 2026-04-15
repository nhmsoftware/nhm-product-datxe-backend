<?php

declare(strict_types=1);

namespace App\Modules\Finance\DTO;

use App\Modules\Finance\Http\Requests\ViewSpendingSummaryRequest;
use Carbon\Carbon;

final class ViewSpendingSummaryDTO
{
    public function __construct(
        public readonly int $customerId,
        public readonly Carbon $startDate,
        public readonly Carbon $endDate,
        public readonly string $rangeLabel
    ) {
    }

    public static function fromRequest(ViewSpendingSummaryRequest $request): self
    {
        $range = $request->string('range')->toString();
        $startDate = now();
        $endDate = now();
        $label = '';

        switch ($range) {
            case 'day':
                $startDate = now()->startOfDay();
                $endDate = now()->endOfDay();
                $label = 'Hôm nay (' . $startDate->format('d/m/Y') . ')';
                break;
            case 'week':
                $startDate = now()->startOfWeek();
                $endDate = now()->endOfWeek();
                $label = 'Tuần này (' . $startDate->format('d/m/Y') . ' - ' . $endDate->format('d/m/Y') . ')';
                break;
            case 'month':
                $startDate = now()->startOfMonth();
                $endDate = now()->endOfMonth();
                $label = 'Tháng này (' . $startDate->format('m/Y') . ')';
                break;
            case 'custom':
                $startDate = Carbon::createFromFormat('Y-m-d', $request->string('start_date')->toString())->startOfDay();
                $endDate = Carbon::createFromFormat('Y-m-d', $request->string('end_date')->toString())->endOfDay();
                $label = 'Từ ' . $startDate->format('d/m/Y') . ' đến ' . $endDate->format('d/m/Y');
                break;
        }

        return new self(
            customerId: (int) $request->user()->id,
            startDate: $startDate,
            endDate: $endDate,
            rangeLabel: $label
        );
    }
}
