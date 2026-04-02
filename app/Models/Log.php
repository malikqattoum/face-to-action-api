<?php

namespace App\Models;

use Database\Factories\LogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Log extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_name',
        'action_taken',
        'amount',
        'currency',
        'next_steps',
        'recorded_at',
        'transcribed_text',
        'audio_path',
        'issue_type',
        'parts_used',
        'estimated_price',
        'service_type',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'recorded_at' => 'datetime',
            'parts_used' => 'array',
            'estimated_price' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function photos()
    {
        return $this->hasMany(LogPhoto::class);
    }
}
