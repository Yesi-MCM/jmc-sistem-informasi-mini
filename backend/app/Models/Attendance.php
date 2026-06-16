<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'employee_id',
        'attendance_import_id',
        'attendance_date',
        'checkin_at',
        'checkout_at',
        'checkin_location',
        'checkout_location',
        'attendance_type',
        'duration_hours',
        'status',
        'verification_status',
        'verified_by_role',
        'remarks'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'duration_hours' => 'decimal:1'
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(AttendanceImport::class, 'attendance_import_id');
    }
}
