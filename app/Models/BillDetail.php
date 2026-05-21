<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillDetail extends Model
{
    use HasFactory;

    protected $fillable = ['bill_id', 'lot_id', 'quantity', 'unit_price', 'subtotal'];

    public function bill()
    {
        return $this->belongsTo(Bill::class);
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }
}
