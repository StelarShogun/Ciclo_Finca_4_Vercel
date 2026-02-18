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
}
