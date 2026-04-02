<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LogPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'log_id',
        'file_path',
        'caption',
    ];

    public function log(): BelongsTo
    {
        return $this->belongsTo(Log::class);
    }

    public function getUrlAttribute(): string
    {
        return url('api/logs/' . $this->log_id . '/photos/' . $this->id);
    }
}
