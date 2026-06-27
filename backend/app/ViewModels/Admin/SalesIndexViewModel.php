<?php

namespace App\ViewModels\Admin;

final class SalesIndexViewModel
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function from(array $payload): array
    {
        return [
            'sales' => array_values((array) ($payload['sales'] ?? [])),
            'pagination' => (array) ($payload['pagination'] ?? []),
            'kpis' => (array) ($payload['kpis'] ?? []),
            'salesStatusUi' => (string) ($payload['salesStatusUi'] ?? 'completed'),
            'latestHistorySaleId' => (int) ($payload['latestHistorySaleId'] ?? 0),
            'filters' => (array) ($payload['filters'] ?? []),
        ];
    }
}
