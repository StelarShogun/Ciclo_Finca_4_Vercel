<?php

namespace App\Services;

use App\Support\AdminDateRange;
use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

class SalesPerformanceDateRangeService
{
    /**
     * @param  array{preset:string,from?:string|null,to?:string|null}  $input
     * @return array{
     *   preset:string,
     *   from:string,
     *   to:string,
     *   current_start:CarbonImmutable,
     *   current_end:CarbonImmutable,
     *   previous_start:CarbonImmutable,
     *   previous_end:CarbonImmutable
     * }
     */
    public function resolve(array $input): array
    {
        $preset = (string) ($input['preset'] ?? 'month');
        $tz = AdminDateRange::timezone();
        $now = CarbonImmutable::now($tz);

        [$currentStart, $currentEnd] = match ($preset) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            'week' => [$now->startOfWeek(Carbon::MONDAY), $now->endOfWeek(Carbon::SUNDAY)],
            'month' => [$now->startOfMonth(), $now->endOfMonth()],
            'year' => [$now->startOfYear(), $now->endOfYear()],
            'custom' => $this->resolveCustomRange($input, $tz),
            default => [$now->startOfMonth(), $now->endOfMonth()],
        };

        [$previousStart, $previousEnd] = $this->equivalentPreviousRange($currentStart, $currentEnd);

        return [
            'preset' => $preset,
            'from' => $currentStart->toDateString(),
            'to' => $currentEnd->toDateString(),
            'current_start' => $currentStart,
            'current_end' => $currentEnd,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
        ];
    }

    /**
     * @param  array{from?:string|null,to?:string|null}  $input
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function resolveCustomRange(array $input, string $timezone): array
    {
        $from = CarbonImmutable::parse((string) ($input['from'] ?? ''), $timezone)->startOfDay();
        $to = CarbonImmutable::parse((string) ($input['to'] ?? ''), $timezone)->endOfDay();

        return [$from, $to];
    }

    /**
     * Previous equivalent period with same duration.
     *
     * @return array{CarbonImmutable, CarbonImmutable}
     */
    private function equivalentPreviousRange(CarbonImmutable $start, CarbonImmutable $end): array
    {
        // Inclusive window: [start, end] — use timestamps to avoid diffInSeconds argument-order quirks.
        $durationSeconds = ($end->timestamp - $start->timestamp) + 1;
        $previousEnd = $start->subSecond();
        $previousStart = $previousEnd->subSeconds($durationSeconds - 1);

        return [$previousStart, $previousEnd];
    }
}
