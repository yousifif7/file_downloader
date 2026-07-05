<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanUpgradeRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    public const PAYMENT_METHOD_BANK = 'bank_transfer';

    public const PAYMENT_METHOD_CRYPTO = 'crypto';

    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'payment_method',
        'provider',
        'provider_payment_id',
        'invoice_url',
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

    public function isCrypto(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_CRYPTO;
    }

    public function isBankTransfer(): bool
    {
        return $this->payment_method === self::PAYMENT_METHOD_BANK;
    }

    public function needsAdminReview(): bool
    {
        return $this->isPending() && $this->isBankTransfer();
    }

    public function isExpiredCryptoInvoice(): bool
    {
        if (! $this->isPending() || ! $this->isCrypto()) {
            return false;
        }

        $expireMinutes = config('billing.crypto.invoice_expire_minutes', 60);

        return $this->created_at->addMinutes($expireMinutes)->isPast();
    }

    public function cancelCryptoInvoice(?string $reason = null): void
    {
        if (! $this->isPending() || ! $this->isCrypto()) {
            return;
        }

        $this->forceFill([
            'status' => self::STATUS_CANCELLED,
            'admin_note' => $reason ?? 'Crypto invoice cancelled.',
            'reviewed_at' => now(),
        ])->save();
    }

    public function paymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            self::PAYMENT_METHOD_CRYPTO => 'Crypto',
            default => 'Bank transfer',
        };
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
            self::STATUS_CANCELLED => 'Cancelled',
            default => $this->isCrypto() ? 'Awaiting payment' : 'Pending review',
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
