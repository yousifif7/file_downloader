<?php

namespace App\Services;

use App\Mail\PlanUpgradeApprovedMail;
use App\Mail\PlanUpgradeRejectedMail;
use App\Mail\PlanUpgradeSubmittedMail;
use App\Models\Plan;
use App\Models\PlanUpgradeRequest;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ManualBillingService
{
    public function paymentReference(User $user, Plan $plan): string
    {
        return 'DL-'.$user->id.'-'.strtoupper($plan->slug);
    }

    public function submitRequest(User $user, Plan $plan, string $paymentReference, ?string $payerNote = null, ?string $receiptPath = null): PlanUpgradeRequest
    {
        $this->assertUpgradeablePlan($plan);

        if ($receiptPath === null) {
            throw ValidationException::withMessages([
                'receipt' => 'A transfer receipt screenshot is required.',
            ]);
        }

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
                'payment_reference' => 'You already have a pending payment review for this plan.',
            ]);
        }

        $request = PlanUpgradeRequest::query()->create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => PlanUpgradeRequest::STATUS_PENDING,
            'payment_method' => PlanUpgradeRequest::PAYMENT_METHOD_BANK,
            'payment_reference' => trim($paymentReference),
            'payer_note' => $payerNote ? trim($payerNote) : null,
            'receipt_path' => $receiptPath,
        ]);

        $request->load(['user', 'plan']);

        Mail::to(config('legal.support_email'))->send(new PlanUpgradeSubmittedMail($request));

        return $request;
    }

    public function storeReceipt(UploadedFile $file, User $user): string
    {
        $directory = public_path('payment-receipts');

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'jpg');
        $filename = sprintf('%d-%s.%s', $user->id, uniqid('', true), $extension);

        $file->move($directory, $filename);

        return 'payment-receipts/'.$filename;
    }

    public function approve(PlanUpgradeRequest $request, User $reviewer, ?string $adminNote = null): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'request' => 'This upgrade request has already been reviewed.',
            ]);
        }

        DB::transaction(function () use ($request, $reviewer, $adminNote): void {
            $request->forceFill([
                'status' => PlanUpgradeRequest::STATUS_APPROVED,
                'admin_note' => $adminNote,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ])->save();

            $this->applyActiveSubscription($request->user, $request->plan_id, config('billing.provider'));

            Mail::to($request->user->email)->send(new PlanUpgradeApprovedMail($request->fresh(['user', 'plan'])));
        });
    }

    private function applyActiveSubscription(User $user, int $planId, string $billingProvider): void
    {
        $user->forceFill([
            'plan_id' => $planId,
            'billing_provider' => $billingProvider,
            'subscription_status' => User::SUBSCRIPTION_ACTIVE,
            'subscription_renews_at' => now()->addMonth(),
            'subscription_ends_at' => null,
        ])->save();
    }

    public function reject(PlanUpgradeRequest $request, User $reviewer, ?string $adminNote = null): void
    {
        if (! $request->isPending()) {
            throw ValidationException::withMessages([
                'request' => 'This upgrade request has already been reviewed.',
            ]);
        }

        $request->forceFill([
            'status' => PlanUpgradeRequest::STATUS_REJECTED,
            'admin_note' => $adminNote,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ])->save();

        $request->load(['user', 'plan']);

        if ($request->user?->email) {
            Mail::to($request->user->email)->send(new PlanUpgradeRejectedMail($request));
        }
    }

    public function grantComplimentary(User $user, Plan $plan, ?\Carbon\Carbon $endsAt = null): void
    {
        $this->assertAssignablePlan($plan);

        $user->forceFill([
            'plan_id' => $plan->id,
            'billing_provider' => config('billing.complimentary_provider'),
            'subscription_status' => User::SUBSCRIPTION_ACTIVE,
            'subscription_renews_at' => null,
            'subscription_ends_at' => $endsAt,
        ])->save();
    }

    public function grantPaidManual(User $user, Plan $plan, ?\Carbon\Carbon $renewsAt = null): void
    {
        $this->assertAssignablePlan($plan);

        $user->forceFill([
            'plan_id' => $plan->id,
            'billing_provider' => config('billing.provider'),
            'subscription_status' => User::SUBSCRIPTION_ACTIVE,
            'subscription_renews_at' => $renewsAt ?? now()->addMonth(),
            'subscription_ends_at' => null,
        ])->save();
    }

    public function revokeToFree(User $user): void
    {
        $freePlan = Plan::query()->where('slug', 'free')->where('is_active', true)->first();

        if ($freePlan === null) {
            throw ValidationException::withMessages([
                'plan' => 'Free plan is not configured.',
            ]);
        }

        $user->forceFill([
            'plan_id' => $freePlan->id,
            'billing_provider' => null,
            'subscription_status' => null,
            'subscription_renews_at' => null,
            'subscription_ends_at' => null,
        ])->save();
    }

    public function assertUpgradeablePlan(Plan $plan): void
    {
        $this->assertAssignablePlan($plan);

        if (! $plan->price_cents) {
            throw ValidationException::withMessages([
                'plan' => 'This plan is not available for upgrade.',
            ]);
        }
    }

    public function assertAssignablePlan(Plan $plan): void
    {
        if (! $plan->is_active || $plan->slug === 'free') {
            throw ValidationException::withMessages([
                'plan' => 'This plan cannot be assigned.',
            ]);
        }
    }
}
