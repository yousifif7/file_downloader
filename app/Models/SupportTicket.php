<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'user_id',
        'subject',
        'download_id',
        'status',
        'user_last_read_at',
        'staff_last_read_at',
        'last_message_at',
        'awaiting_staff',
    ];

    protected function casts(): array
    {
        return [
            'user_last_read_at' => 'datetime',
            'staff_last_read_at' => 'datetime',
            'last_message_at' => 'datetime',
            'awaiting_staff' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function download(): BelongsTo
    {
        return $this->belongsTo(Download::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at');
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function hasUnreadForUser(): bool
    {
        if ($this->last_message_at === null) {
            return false;
        }

        return $this->messages()
            ->where('is_staff', true)
            ->when($this->user_last_read_at, fn ($query) => $query->where('created_at', '>', $this->user_last_read_at))
            ->exists();
    }

    public function hasUnreadForStaff(): bool
    {
        return $this->awaiting_staff;
    }

    public function markReadByUser(): void
    {
        $this->forceFill(['user_last_read_at' => now()])->save();
    }

    public function markReadByStaff(): void
    {
        $this->forceFill(['staff_last_read_at' => now()])->save();
    }
}
