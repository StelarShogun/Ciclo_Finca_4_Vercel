<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    protected $table = 'sales';
    protected $primaryKey = 'sale_id';

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'seller_id',
        'subtotal',
        'iva',
        'discount',
        'total',
        'payment_method',
        'payment_reference',
        'status',
        'notes',
        'sale_date',
    ];

    protected $casts = [
        'sale_date' => 'datetime',
        'subtotal' => 'decimal:2',
        'iva' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'sale_id', 'sale_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'customer_id', 'usuario_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'seller_id', 'usuario_id');
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

    public function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $date = now()->format('Ymd');
        $lastNumber = self::whereDate('sale_date', now())->count() + 1;
        return $prefix . $date . str_pad($lastNumber, 4, '0', STR_PAD_LEFT);
    }

    public function calculateTotal()
    {
        return $this->subtotal + $this->iva - $this->discount;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'completed']);
    }

      /**
     * Días de vigencia configurados para eliminación automática.
     */
    public static function getOrderExpirationDays(): int 
    {
        return (int) config('sales.order_expiration_days');
    }

    /**
     * Fecha límite: después de esta fecha el pedido será eliminado.
     */
    public function getExpiresAtAttribute(): \Carbon\Carbon
      {
        $days = static::getOrderExpirationDays();
        return $this->sale_date->addDays($days);
      }
    
      /**
     * Días restantes hasta la eliminación automática (0 si ya expiró).
     */
      public function getDaysRemainingUntilExpiration(): int
       {
        $expiresAt = $this->expires_at;
        $now = now();
        if ($expiresAt <= $now) {
            return 0;
        }
        return (int) $now->diffInDays($expiresAt , false);
       }


       /**
     * Indica si quedan dos días o menos (para mostrar alerta).
     */
     public function getIsExpriryWarningAttribute(): bool
      {
        return $this->days_remaining_until_expiration <= config('sales.expiry_alert_days')
            && $this->days_remaining_until_expiration > 0;
      }

        /**
     * Scope: solo pedidos que aún no han superado el tiempo de vigencia.
     */
      public function scopeNotExpired($query)
      {
        $days = static::getOrderExpirationDays();
        $limitDate = now()->addDays($days);
        return $query->where('sale_date', '>=', $limitDate);
      }


    }

