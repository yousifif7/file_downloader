@extends('pages._legal')

@section('title', 'Refund Policy')

@section('meta_description', 'Refund Policy for '.config('legal.business_name').'. How refunds work for paid subscriptions and when you can request your money back.')

@section('canonical', route('refund'))

@section('page_title', 'Refund Policy')

@section('page_body')
    <section>
        <h2 class="text-lg font-semibold text-white mb-3">1. Overview</h2>
        <p>
            {{ config('legal.business_name') }} is operated by {{ config('legal.operator_name') }}.
            This Refund Policy explains when you can request a refund for a paid subscription paid by bank transfer.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">2. Free plan</h2>
        <p>The free plan does not involve payment. No refunds apply because no charge is made.</p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">3. Paid subscriptions</h2>
        <p>
            Paid plans are billed on a recurring monthly basis. When you upgrade, you receive the download limits and platform access described on the pricing page at the time of purchase.
        </p>
        <p class="mt-3">
            Payments are made by bank transfer to our business account. After we verify your transfer, your plan is activated manually in your account.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">4. {{ config('legal.refund_window_days') }}-day refund window</h2>
        <p>
            If you are unhappy with a paid plan, you may request a full refund within <strong class="text-slate-200">{{ config('legal.refund_window_days') }} days</strong> of your first payment for that subscription, provided that:
        </p>
        <ul class="list-disc list-inside mt-2 space-y-1 text-slate-400">
            <li>You contact us at <a href="mailto:{{ config('legal.support_email') }}" class="text-violet-400 hover:underline">{{ config('legal.support_email') }}</a> from the email address on your account</li>
            <li>You explain the issue (for example, downloads consistently failing for supported platforms)</li>
            <li>You have not abused the service or violated our <a href="{{ route('terms') }}" class="text-violet-400 hover:underline">Terms of Service</a></li>
        </ul>
        <p class="mt-3">
            Refunds are not available for partial billing periods after the refund window has passed, except where required by applicable law.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">5. What we do not refund</h2>
        <ul class="list-disc list-inside space-y-1 text-slate-400">
            <li>Renewal charges after you have already used a paid plan for a full billing cycle without contacting us during the refund window</li>
            <li>Failures caused by unsupported URLs, private content, or platform restrictions outside our control</li>
            <li>Accounts suspended or terminated for abuse, fraud, or Terms violations</li>
            <li>Bank or payment-provider fees charged by third parties</li>
        </ul>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">6. Cancellations</h2>
        <p>
            You can cancel a paid subscription at any time. Cancellation stops future renewals. You keep access until the end of the current billing period unless a refund is approved under section 4.
        </p>
        <p class="mt-3">
            To cancel, open a <a href="{{ route('support') }}" class="text-violet-400 hover:underline">support conversation</a> before your next renewal. Do not send another bank transfer if you want to stop.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">7. How to request a refund</h2>
        <p>
            Email <a href="mailto:{{ config('legal.support_email') }}" class="text-violet-400 hover:underline">{{ config('legal.support_email') }}</a>
            or open a <a href="{{ route('support') }}" class="text-violet-400 hover:underline">support conversation</a> after signing in.
            Include your account email and your bank transfer reference.
            We aim to respond within {{ config('legal.support_response_hours') }} hours on business days.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">8. Changes</h2>
        <p>We may update this policy. The date at the top of this page shows when it was last revised.</p>
    </section>
@endsection
