<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Medicine extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['category_id', 'laboratory_id', 'sanitary_registry_id', 'name', 'generic_name', 'concentration_value', 'concentration_unit_id', 'container_id', 'content_quantity', 'content_unit_id', 'is_cold_chain', 'is_special_control', 'min_stock', 'selling_price', 'description', 'created_by', 'updated_by', 'deleted_by'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function laboratory()
    {
        return $this->belongsTo(Laboratory::class);
    }

    public function sanitaryRegistry()
    {
        return $this->belongsTo(SanitaryRegistry::class);
    }

    public function concentrationUnit()
    {
        return $this->belongsTo(ConcentrationUnit::class);
    }

    public function container()
    {
        return $this->belongsTo(Container::class);
    }

    public function contentUnit()
    {
        return $this->belongsTo(ContentUnit::class);
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

    public function barcodes()
    {
        return $this->hasMany(MedicineBarcode::class);
    }

    public function purchaseOrderDetails()
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }

    public function lots()
    {
        return $this->hasMany(Lot::class);
    }
}
