<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    protected $fillable = [
        'user_id',
        'session_token',
        'remember_me',
        'ip_address',
        'user_agent',
        'last_activity_at',
        'expires_at',
        'logged_out_at'
    ];

    protected $casts = [
        'remember_me' => 'boolean',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
        'logged_out_at' => 'datetime'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
