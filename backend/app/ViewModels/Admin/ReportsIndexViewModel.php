<?php

namespace App\ViewModels\Admin;

final class ReportsIndexViewModel
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public static function from(array $filters = []): array
    {
        return [
            'filters' => [
                'period' => (string) ($filters['period'] ?? 'month'),
                'from' => (string) ($filters['from'] ?? ''),
                'to' => (string) ($filters['to'] ?? ''),
            ],
        ];
    }
}
