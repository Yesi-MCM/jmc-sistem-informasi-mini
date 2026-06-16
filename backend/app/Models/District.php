<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    protected $fillable = ['regency_id', 'code', 'name'];

    public function regency(): BelongsTo
    {
        return $this->belongsTo(Regency::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
