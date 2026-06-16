<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportAllowanceSetting extends Model
{
    protected $table = 'transport_allowance_settings';

    protected $fillable = [
        'base_fare',
        'effective_start',
        'min_km',
        'max_km',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'base_fare' => 'decimal:2',
        'effective_start' => 'date',
        'min_km' => 'decimal:2',
        'max_km' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
