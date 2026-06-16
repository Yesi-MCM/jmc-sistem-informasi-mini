<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nip',
        'name',
        'email',
        'phone',
        'photo_path',
        'birth_place',
        'birth_date',
        'marital_status',
        'children_count',
        'joined_at',
        'position_id',
        'department_id',
        'employment_type',
        'gender',
        'distance_km',
        'district_id',
        'full_address',
        'status',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'birth_date' => 'date',
        'joined_at' => 'date',
        'distance_km' => 'decimal:2',
        'children_count' => 'integer'
    ];

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(EmployeeEducation::class)->orderBy('sort_order');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function summaries(): HasMany
    {
        return $this->hasMany(AttendanceSummary::class);
    }

    /**
     * Calculate employee work tenure (masa kerja) in years and months.
     */
    public function getWorkTenureAttribute(): string
    {
        if (!$this->joined_at) {
            return '-';
        }
        $diff = $this->joined_at->diff(now());
        $years = $diff->y;
        $months = $diff->m;

        if ($years > 0) {
            return $years . ' Tahun ' . $months . ' Bulan';
        }
        return $months . ' Bulan';
    }

    /**
     * Calculate age (usia) based on birth_date.
     */
    public function getAgeAttribute(): int
    {
        if (!$this->birth_date) {
            return 0;
        }
        return $this->birth_date->age;
    }
}
