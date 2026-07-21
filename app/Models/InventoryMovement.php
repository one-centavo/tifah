<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryMovement extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = ['lot_id', 'type', 'quantity', 'previous_balance', 'new_balance', 'concept', 'reference_id', 'created_at', 'created_by'];

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
