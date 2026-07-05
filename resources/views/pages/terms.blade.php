@extends('pages._legal')

@section('title', 'Terms of Service')

@section('meta_description', 'Terms of Service for '.config('legal.business_name').'. Rules for using our online video downloader, accounts, subscriptions, download limits, and acceptable use.')

@section('canonical', route('terms'))

@section('page_title', 'Terms of Service')

@section('page_body')
    <section>
        <h2 class="text-lg font-semibold text-white mb-3">1. Acceptance</h2>
        <p>
            By using {{ config('legal.business_name') }} ("the Service"), operated by {{ config('legal.operator_name') }}, you agree to these Terms.
            If you do not agree, do not use the Service.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">2. What the Service does</h2>
        <p>
            The Service lets registered users submit URLs to download media or files from supported third-party platforms.
            Downloads are subject to monthly limits and platform access based on your plan.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">3. Your responsibility</h2>
        <p>You are solely responsible for the URLs you submit and the content you download. You must:</p>
        <ul class="list-disc list-inside mt-2 space-y-1 text-slate-400">
            <li>Only download content you own or have explicit permission to download</li>
            <li>Comply with the terms of the source platform and applicable copyright laws</li>
            <li>Not use the Service for piracy, harassment, or illegal activity</li>
        </ul>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">4. Accounts & limits</h2>
        <p>
            Free accounts receive a limited number of downloads per month on selected platforms.
            Paid plans offer higher limits and access to additional platforms as described on the pricing page.
        </p>
        <p class="mt-3">
            We may suspend or terminate accounts that abuse the Service, attempt to bypass limits, or violate these Terms.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">5. Paid subscriptions & billing</h2>
        <p>
            Paid plans are billed monthly by bank transfer. After we verify your payment, your plan is activated in your account.
        </p>
        <ul class="list-disc list-inside mt-2 space-y-1 text-slate-400">
            <li>Prices are shown on the upgrade page before you pay</li>
            <li>Plans renew monthly when you send a new transfer before the renewal date</li>
            <li>Refunds are governed by our <a href="{{ route('refund') }}" class="text-violet-400 hover:underline">Refund Policy</a></li>
        </ul>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">6. No warranty</h2>
        <p>
            The Service is provided "as is." We do not guarantee that every URL will work, that downloads will succeed,
            or that files will remain available after the stated expiry period. Third-party platforms may change without notice.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">7. Limitation of liability</h2>
        <p>
            To the fullest extent permitted by law, we are not liable for any damages arising from your use of the Service or downloaded content.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">8. Privacy</h2>
        <p>
            Our <a href="{{ route('privacy') }}" class="text-violet-400 hover:underline">Privacy Policy</a> explains how we collect and use your data.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">9. Changes</h2>
        <p>We may update these Terms at any time. Continued use of the Service after changes constitutes acceptance.</p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">10. Contact</h2>
        <p>
            Questions? Email <a href="mailto:{{ config('legal.support_email') }}" class="text-violet-400 hover:underline">{{ config('legal.support_email') }}</a>
            or visit our <a href="{{ route('support') }}" class="text-violet-400 hover:underline">support page</a>.
        </p>
    </section>
@endsection
