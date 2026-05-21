<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SanitaryRegistry extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['registration_number', 'laboratory_id', 'expiration_date', 'status', 'description', 'created_by', 'updated_by', 'deleted_by'];

    public function laboratory()
    {
        return $this->belongsTo(Laboratory::class);
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

    public function medicines()
    {
        return $this->hasMany(Medicine::class);
    }
}
