<?php

namespace App\Services;

use App\Mail\PlanUpgradeApprovedMail;
use App\Models\Plan;
use App\Models\PlanUpgradeRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class PlisioBillingService
{
    public function __construct(
        private ManualBillingService $manualBilling,
    ) {}

    public function isAvailable(): bool
    {
        return config('billing.crypto.enabled')
            && filled(config('billing.crypto.api_key'));
    }

    public function createCheckout(User $user, Plan $plan): PlanUpgradeRequest
    {
        $this->manualBilling->assertUpgradeablePlan($plan);

        if (! $this->isAvailable()) {
            throw ValidationException::withMessages([
                'payment' => 'Crypto payments are not available right now. Please use bank transfer or contact support.',
            ]);
        }

        $this->expireStaleCryptoRequests();

        if ($user->plan_id === $plan->id && $user->hasActiveSubscription()) {
            throw ValidationException::withMessages([
                'plan' => 'You are already on this plan.',
            ]);
        }

        $hasPending = PlanUpgradeRequest::query()
            ->where('user_id', $user->id)
            ->where('plan_id', $plan->id)
            ->where('status', PlanUpgradeRequest::STATUS_PENDING)
            ->exists();

        if ($hasPending) {
            throw ValidationException::withMessages([
                'payment' => 'You already have a pending payment for this plan.',
            ]);
        }

        $paymentReference = $this->manualBilling->paymentReference($user, $plan);

        $upgradeRequest = PlanUpgradeRequest::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => PlanUpgradeRequest::STATUS_PENDING,
            'payment_method' => PlanUpgradeRequest::PAYMENT_METHOD_CRYPTO,
            'provider' => config('billing.crypto.provider'),
            'payment_reference' => $paymentReference,
        ]);

        $response = Http::timeout(20)
            ->get('https://api.plisio.net/api/v1/invoices/new', $this->invoiceParams($user, $plan, $upgradeRequest));

        if (! $response->successful()) {
            $upgradeRequest->delete();

            throw ValidationException::withMessages([
                'payment' => 'Could not start crypto checkout. Please try again or use bank transfer.',
            ]);
        }

        $payload = $response->json();

        if (($payload['status'] ?? null) !== 'success' || empty($payload['data']['invoice_url'])) {
            $upgradeRequest->delete();

            $message = data_get($payload, 'data.message', 'Could not create crypto invoice.');

            throw ValidationException::withMessages([
                'payment' => is_string($message) ? $message : 'Could not start crypto checkout. Please try again or use bank transfer.',
            ]);
        }

        $upgradeRequest->forceFill([
            'provider_payment_id' => $payload['data']['txn_id'] ?? null,
            'invoice_url' => $payload['data']['invoice_url'],
        ])->save();

        return $upgradeRequest;
    }

    public function verifyCallback(array $data): bool
    {
        $secretKey = config('billing.crypto.api_key');

        if (! isset($data['verify_hash']) || ! filled($secretKey)) {
            return false;
        }

        $verifyHash = $data['verify_hash'];
        $checkKey = $this->callbackSignature($data, $secretKey);

        return hash_equals($checkKey, $verifyHash);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function callbackSignature(array $data, ?string $secretKey = null): string
    {
        $secretKey ??= config('billing.crypto.api_key');

        unset($data['verify_hash']);
        ksort($data);

        if (isset($data['expire_utc'])) {
            $data['expire_utc'] = (string) $data['expire_utc'];
        }

        if (isset($data['tx_urls'])) {
            $data['tx_urls'] = html_entity_decode((string) $data['tx_urls']);
        }

        return hash_hmac('sha1', json_encode($data, JSON_UNESCAPED_SLASHES), $secretKey);
    }

    public function handleCallback(array $data): void
    {
        if (! $this->verifyCallback($data)) {
            throw ValidationException::withMessages([
                'callback' => 'Invalid Plisio callback signature.',
            ]);
        }

        if (($data['status'] ?? null) === 'completed') {
            $upgradeRequest = $this->resolveUpgradeRequest($data);

            if ($upgradeRequest === null || $upgradeRequest->status === PlanUpgradeRequest::STATUS_APPROVED) {
                return;
            }

            $this->activateSubscription($upgradeRequest);

            return;
        }

        if ($this->isFailureStatus($data['status'] ?? null)) {
            $upgradeRequest = $this->resolveUpgradeRequest($data);

            if ($upgradeRequest !== null && $upgradeRequest->isPending() && $upgradeRequest->isCrypto()) {
                $this->cancelRequest($upgradeRequest, $this->failureReason($data));
            }
        }
    }

    public function expireStaleCryptoRequests(): int
    {
        $expireMinutes = config('billing.crypto.invoice_expire_minutes', 60);
        $cutoff = now()->subMinutes($expireMinutes);

        $stale = PlanUpgradeRequest::query()
            ->where('status', PlanUpgradeRequest::STATUS_PENDING)
            ->where('payment_method', PlanUpgradeRequest::PAYMENT_METHOD_CRYPTO)
            ->where('created_at', '<', $cutoff)
            ->get();

        foreach ($stale as $request) {
            $this->cancelRequest($request, 'Crypto invoice expired without payment.');
        }

        return $stale->count();
    }

    public function cancelRequest(PlanUpgradeRequest $upgradeRequest, ?string $reason = null): void
    {
        if (! $upgradeRequest->isPending() || ! $upgradeRequest->isCrypto()) {
            return;
        }

        $upgradeRequest->cancelCryptoInvoice($reason);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function failureReason(array $data): string
    {
        return match ($data['status'] ?? null) {
            'expired' => 'Crypto invoice expired without payment.',
            'cancelled', 'cancelled duplicate' => 'Crypto checkout was cancelled.',
            'error' => 'Crypto payment could not be completed.',
            default => 'Crypto payment was not completed.',
        };
    }

    private function isFailureStatus(?string $status): bool
    {
        return in_array($status, ['expired', 'cancelled', 'cancelled duplicate', 'error'], true);
    }

    public function activateSubscription(PlanUpgradeRequest $upgradeRequest): void
    {
        if ($upgradeRequest->status === PlanUpgradeRequest::STATUS_APPROVED) {
            return;
        }

        DB::transaction(function () use ($upgradeRequest): void {
            $upgradeRequest->refresh();

            if ($upgradeRequest->status === PlanUpgradeRequest::STATUS_APPROVED) {
                return;
            }

            $upgradeRequest->forceFill([
                'status' => PlanUpgradeRequest::STATUS_APPROVED,
                'admin_note' => 'Activated automatically via crypto payment.',
                'reviewed_at' => now(),
            ])->save();

            $user = $upgradeRequest->user;
            $user->forceFill([
                'plan_id' => $upgradeRequest->plan_id,
                'billing_provider' => config('billing.crypto.provider'),
                'subscription_status' => User::SUBSCRIPTION_ACTIVE,
                'subscription_renews_at' => now()->addMonth(),
                'subscription_ends_at' => null,
            ])->save();

            Mail::to($user->email)->send(new PlanUpgradeApprovedMail($upgradeRequest->fresh(['user', 'plan'])));
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function invoiceParams(User $user, Plan $plan, PlanUpgradeRequest $upgradeRequest): array
    {
        return [
            'api_key' => config('billing.crypto.api_key'),
            'source_currency' => config('billing.currency'),
            'source_amount' => number_format($plan->price_cents / 100, 2, '.', ''),
            'order_number' => (string) $upgradeRequest->id,
            'order_name' => $plan->name.' monthly plan',
            'currency' => config('billing.crypto.default_currency'),
            'allowed_psys_cids' => config('billing.crypto.allowed_currencies'),
            'description' => $this->manualBilling->paymentReference($user, $plan),
            'email' => $user->email,
            'callback_url' => route('webhooks.plisio', ['json' => 'true']),
            'success_invoice_url' => route('upgrade.crypto.success', $plan),
            'fail_invoice_url' => route('upgrade.show', $plan),
            'expire_min' => config('billing.crypto.invoice_expire_minutes'),
            'plugin' => 'laravel',
            'version' => '1.0.0',
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveUpgradeRequest(array $data): ?PlanUpgradeRequest
    {
        $orderNumber = $data['order_number'] ?? null;

        if ($orderNumber !== null) {
            $request = PlanUpgradeRequest::query()->find($orderNumber);

            if ($request !== null) {
                return $request;
            }
        }

        $txnId = $data['txn_id'] ?? null;

        if ($txnId === null) {
            return null;
        }

        return PlanUpgradeRequest::query()
            ->where('provider_payment_id', $txnId)
            ->first();
    }
}
