<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lot extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['medicine_id', 'purchase_order_id', 'batch_number', 'expiration_date', 'current_quantity', 'initial_quantity', 'reception_date', 'unit_purchase_price', 'status', 'created_by', 'updated_by', 'deleted_by'];

    public function medicine()
    {
        return $this->belongsTo(Medicine::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function billDetails()
    {
        return $this->hasMany(BillDetail::class);
    }

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
