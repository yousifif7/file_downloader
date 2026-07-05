<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'staff_user_id',
        'is_staff',
        'body',
    ];

    protected function casts(): array
    {
        return [
            'is_staff' => 'boolean',
        ];
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'support_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function staffUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_user_id');
    }

    public function authorName(): string
    {
        if ($this->is_staff) {
            return $this->staffUser?->name ?? config('legal.business_name').' Support';
        }

        return $this->user?->name ?? $this->ticket?->user?->name ?? 'Customer';
    }
}
