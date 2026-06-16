<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransportAllowanceDetail extends Model
{
    protected $table = 'transport_allowance_details';

    protected $fillable = [
        'transport_allowance_period_id',
        'employee_id',
        'base_fare',
        'original_km',
        'rounded_km',
        'attendance_days',
        'nominal',
        'eligibility_status',
        'calculation_note'
    ];

    protected $casts = [
        'base_fare' => 'decimal:2',
        'original_km' => 'decimal:2',
        'rounded_km' => 'integer',
        'attendance_days' => 'integer',
        'nominal' => 'decimal:2'
    ];

    public function period(): BelongsTo
    {
        return $this->belongsTo(TransportAllowancePeriod::class, 'transport_allowance_period_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
