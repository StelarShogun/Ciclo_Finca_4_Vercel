<?php

namespace App\Services\Client\Storefront;

use App\Models\Sale;

/**
 * Client-facing pickup / retiro copy (web + email).
 */
class ClientPickupPolicy
{
    public static function expirationHours(): int
    {
        return Sale::getReadyToPickupExpirationHours();
    }

    public static function windowLabel(): string
    {
        return static::windowLabelFromHours(static::expirationHours());
    }

    public static function windowLabelFromHours(int $hours): string
    {
        if ($hours >= 24 && $hours % 24 === 0) {
            $days = (int) ($hours / 24);

            return $days === 1 ? '1 día hábil' : "{$days} días hábiles";
        }

        return $hours === 1 ? '1 hora' : "{$hours} horas";
    }

    public static function summaryLine(): string
    {
        return static::summaryLineFromHours(static::expirationHours());
    }

    public static function summaryLineFromHours(int $hours): string
    {
        $window = static::windowLabelFromHours($hours);

        return 'Cuando tu pedido esté listo para recoger, tendrás '
            ."{$window} para retirarlo en tienda.";
    }

    public static function expiryConsequenceLine(): string
    {
        return 'Si no lo retiras dentro de ese plazo, el pedido puede cancelarse automáticamente '
            .'y el stock volverá a estar disponible.';
    }

    public static function fullNotice(): string
    {
        return static::summaryLine().' '.static::expiryConsequenceLine()
            .' Recuerda llevar tu número de pedido o identificación al retirar.';
    }

    /** @return list<string> */
    public static function emailParagraphs(): array
    {
        return [
            static::summaryLine(),
            static::expiryConsequenceLine(),
            'Recuerde traer su número de pedido o identificación al momento de recogerlo.',
        ];
    }
}
