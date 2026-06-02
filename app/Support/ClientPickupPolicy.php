<?php

namespace App\Support;

/**
 * @deprecated Use {@see \App\Services\Client\Storefront\ClientPickupPolicy}.
 */
class ClientPickupPolicy
{
    public static function expirationHours(): int
    {
        return \App\Services\Client\Storefront\ClientPickupPolicy::expirationHours();
    }

    public static function windowLabel(): string
    {
        return \App\Services\Client\Storefront\ClientPickupPolicy::windowLabel();
    }

    public static function windowLabelFromHours(int $hours): string
    {
        return \App\Services\Client\Storefront\ClientPickupPolicy::windowLabelFromHours($hours);
    }

    public static function summaryLine(): string
    {
        return \App\Services\Client\Storefront\ClientPickupPolicy::summaryLine();
    }

    public static function summaryLineFromHours(int $hours): string
    {
        return \App\Services\Client\Storefront\ClientPickupPolicy::summaryLineFromHours($hours);
    }

    public static function expiryConsequenceLine(): string
    {
        return \App\Services\Client\Storefront\ClientPickupPolicy::expiryConsequenceLine();
    }

    public static function fullNotice(): string
    {
        return \App\Services\Client\Storefront\ClientPickupPolicy::fullNotice();
    }

    /** @return list<string> */
    public static function emailParagraphs(): array
    {
        return \App\Services\Client\Storefront\ClientPickupPolicy::emailParagraphs();
    }
}
