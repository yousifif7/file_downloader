<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Download extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'user_id',
        'platform_id',
        'source_url',
        'media_title',
        'format',
        'status',
        'file_path',
        'file_size_bytes',
        'error_message',
        'ip_address',
        'expires_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function platform(): BelongsTo
    {
        return $this->belongsTo(Platform::class);
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_COMPLETED
            && $this->file_path !== null
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
