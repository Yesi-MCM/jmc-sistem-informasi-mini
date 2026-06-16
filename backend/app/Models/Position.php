<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    protected $fillable = ['code', 'name', 'position_type'];

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
