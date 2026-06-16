<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSummary extends Model
{
    protected $table = 'attendance_summaries';

    protected $fillable = [
        'employee_id',
        'period_year',
        'period_month',
        'hadir',
        'cuti',
        'kuota_cuti',
        'izin',
        'kuota_izin',
        'unpaid_leave',
        'kuota_unpaid_leave',
        'status_hadir',
        'calculated_at'
    ];

    protected $casts = [
        'hadir' => 'decimal:1',
        'cuti' => 'decimal:1',
        'kuota_cuti' => 'decimal:1',
        'izin' => 'decimal:1',
        'kuota_izin' => 'decimal:1',
        'unpaid_leave' => 'decimal:1',
        'kuota_unpaid_leave' => 'decimal:1',
        'calculated_at' => 'datetime'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
