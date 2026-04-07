<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $table = 'sales';

    protected $primaryKey = 'sale_id';

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'client_id',
        'seller_id',
        'seller_admin_id',
        'subtotal',
        'iva',
        'discount',
        'total',
        'payment_method',
        'payment_reference',
        'status',
        'notes',
        'sale_date',
        'buyer_name',
        'buyer_email',
        'order_source',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    // Converts sale_date from UTC to the application timezone for consistent display
    public function getSaleDateAttribute($value)
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse($value, 'UTC')->setTimezone(config('app.timezone'));
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'sale_id', 'sale_id');
    }

    public function sellerAdmin(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'seller_admin_id', 'user_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id', 'user_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('sale_date', $date);
    }

    // Sequential suffix is based on today's count, so numbers reset each day
    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $lastNumber = self::whereDate('sale_date', now())->count() + 1;

        return $prefix.$date.str_pad($lastNumber, 4, '0', STR_PAD_LEFT);
    }

    public function calculateTotal()
    {
        return $this->subtotal + $this->iva - $this->discount;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'completed']);
    }

    public static function getOrderExpirationDays(): int
    {
        return (int) config('sales.order_expiration_days', 30);
    }

    public function getExpiresAtAttribute(): Carbon
    {
        $days = static::getOrderExpirationDays();

        return $this->sale_date->copy()->addDays($days);
    }

    public function getDaysRemainingUntilExpirationAttribute(): int
    {
        $expiresAt = $this->expires_at;
        $now = now();
        if ($expiresAt <= $now) {
            return 0;
        }

        return (int) $now->diffInDays($expiresAt, false);
    }

    public function getIsExpiryWarningAttribute(): bool
    {
        $days = $this->days_remaining_until_expiration;

        // Triggers when at or below the alert threshold but not yet expired
        return $days <= (int) config('sales.expiry_alert_days', 2) && $days > 0;
    }

    public function scopeNotExpired($query)
    {
        $days = static::getOrderExpirationDays();
        $limitDate = now()->subDays($days);

        return $query->where('sale_date', '>=', $limitDate);
    }
}
