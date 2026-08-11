<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_customer',
        'invoice_number',
        'status',
        'payment_method',
        'payment_due_date',
        'total_amount',
        'annulled_reason',
        'annulled_by',
        'annulled_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'payment_due_date' => 'date',
            'annulled_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Bill $bill) {
            throw new \RuntimeException('Las facturas registradas no pueden eliminarse. Deben ser anuladas.');
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'id_customer');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function annuller()
    {
        return $this->belongsTo(User::class, 'annulled_by');
    }

    public function details()
    {
        return $this->hasMany(BillDetail::class);
    }
}

