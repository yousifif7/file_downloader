<?php

namespace App\Models;

use App\Services\SubscriptionAccessService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    public const SUBSCRIPTION_ACTIVE = 'active';

    public const SUBSCRIPTION_PAST_DUE = 'past_due';

    public const SUBSCRIPTION_CANCELED = 'canceled';

    public const SUBSCRIPTION_EXPIRED = 'expired';

    public const SUBSCRIPTION_TRIALING = 'trialing';

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'plan_id',
        'downloads_this_month',
        'quota_reset_at',
        'subscription_status',
        'billing_provider',
        'billing_provider_customer_id',
        'billing_provider_subscription_id',
        'subscription_renews_at',
        'subscription_ends_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'quota_reset_at' => 'date',
            'subscription_renews_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function planUpgradeRequests(): HasMany
    {
        return $this->hasMany(PlanUpgradeRequest::class);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public static function subscriptionStatuses(): array
    {
        return [
            self::SUBSCRIPTION_ACTIVE => 'Active',
            self::SUBSCRIPTION_PAST_DUE => 'Past due',
            self::SUBSCRIPTION_CANCELED => 'Canceled',
            self::SUBSCRIPTION_EXPIRED => 'Expired',
            self::SUBSCRIPTION_TRIALING => 'Trialing',
        ];
    }

    public function subscriptionStatusLabel(): string
    {
        if (app(SubscriptionAccessService::class)->isComplimentary($this)) {
            return 'Complimentary';
        }

        if ($this->subscription_status === null) {
            return $this->plan?->slug === 'free' || $this->plan_id === null
                ? 'Free'
                : 'Manual';
        }

        return self::subscriptionStatuses()[$this->subscription_status] ?? ucfirst(str_replace('_', ' ', $this->subscription_status));
    }

    public function isComplimentary(): bool
    {
        return app(SubscriptionAccessService::class)->isComplimentary($this);
    }

    public function hasActiveSubscription(): bool
    {
        return in_array($this->subscription_status, [
            self::SUBSCRIPTION_ACTIVE,
            self::SUBSCRIPTION_TRIALING,
        ], true);
    }
}
