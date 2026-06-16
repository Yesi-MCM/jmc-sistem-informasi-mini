<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TransportAllowancePeriod extends Model
{
    protected $table = 'transport_allowance_periods';

    protected $fillable = [
        'period_year',
        'period_month',
        'total_recipients',
        'total_amount',
        'status',
        'calculated_by',
        'calculated_at'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'calculated_at' => 'datetime',
        'total_recipients' => 'integer'
    ];

    public function calculator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'calculated_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(TransportAllowanceDetail::class, 'transport_allowance_period_id');
    }
}
