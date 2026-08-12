<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpirationAlertDismissal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'dismissed_date',
    ];

    protected function casts(): array
    {
        return [
            'dismissed_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
