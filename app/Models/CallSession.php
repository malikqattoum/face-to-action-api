<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CallSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'contact_name',
        'phone_number',
        'direction', // 'incoming' | 'outgoing' | 'missed'
        'duration_seconds',
        'started_at',
        'ended_at',
        'notes',
        'has_voice_memo',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'has_voice_memo' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function log(): HasOne
    {
        return $this->hasOne(Log::class);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
