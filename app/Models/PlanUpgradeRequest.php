<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanUpgradeRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'payment_reference',
        'payer_note',
        'receipt_path',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
        'user_dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'reviewed_at' => 'datetime',
            'user_dismissed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isDismissedByUser(): bool
    {
        return $this->user_dismissed_at !== null;
    }

    public function dismissForUser(): void
    {
        if ($this->isPending() || $this->isDismissedByUser()) {
            return;
        }

        $this->forceFill(['user_dismissed_at' => now()])->save();
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Pending review',
        };
    }

    public function receiptUrl(): ?string
    {
        if ($this->receipt_path === null) {
            return null;
        }

        return asset($this->receipt_path);
    }
}
