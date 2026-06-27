<?php

namespace App\ViewModels\Admin;

final class SupplierOrderIndexViewModel
{
    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function from(array $payload): array
    {
        return [
            'orders' => array_values((array) ($payload['orders'] ?? [])),
            'pagination' => (array) ($payload['pagination'] ?? []),
            'openSupplierOrdersCount' => (int) ($payload['openSupplierOrdersCount'] ?? 0),
            'suppliers' => array_values((array) ($payload['suppliers'] ?? [])),
            'filters' => (array) ($payload['filters'] ?? []),
        ];
    }
}
