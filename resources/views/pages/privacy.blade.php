@extends('pages._legal')

@section('title', 'Privacy Policy')

@section('meta_description', 'Privacy Policy for '.config('legal.business_name').'. What we collect, how downloads are processed, billing data, file storage, and your data rights.')

@section('canonical', route('privacy'))

@section('page_title', 'Privacy Policy')

@section('page_body')
    <section>
        <h2 class="text-lg font-semibold text-white mb-3">1. Overview</h2>
        <p>
            {{ config('legal.business_name') }}, operated by {{ config('legal.operator_name') }}, respects your privacy.
            This policy explains what we collect and how we use it.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">2. Information we collect</h2>
        <ul class="list-disc list-inside space-y-1 text-slate-400">
            <li><strong class="text-slate-300">Account data:</strong> name, email, and password (hashed)</li>
            <li><strong class="text-slate-300">Usage data:</strong> URLs you submit, download history, IP address, and timestamps</li>
            <li><strong class="text-slate-300">Billing data:</strong> plan, subscription status, and payment references from our payment provider (we do not store full card numbers)</li>
            <li><strong class="text-slate-300">Support messages:</strong> information you send when contacting support</li>
            <li><strong class="text-slate-300">Technical data:</strong> session cookies required for login and security</li>
        </ul>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">3. How we use it</h2>
        <p>
            We use your data to operate the Service: process downloads, enforce quotas, manage subscriptions,
            respond to support requests, prevent abuse, and maintain your account. We do not sell your personal information.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">4. Payment processing</h2>
        <p>
            Paid subscriptions are paid by bank transfer to our business account.
            We store your plan status, renewal dates, and the payment reference you submit — not card numbers.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">5. File storage</h2>
        <p>
            Downloaded files are stored temporarily on our servers and automatically deleted after the configured expiry period.
            We do not build a permanent library of your content.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">6. Third-party platforms</h2>
        <p>
            When you submit a URL, our servers fetch content from third-party platforms on your behalf.
            We do not share your account details with those platforms.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">7. Data retention</h2>
        <p>
            Account data is kept while your account is active.
            Download logs may be retained for operational and abuse-prevention purposes.
            You may request account deletion via your profile settings.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">8. Security</h2>
        <p>
            We use industry-standard practices including hashed passwords, signed download links, and access controls.
            No method of transmission over the internet is 100% secure.
        </p>
    </section>

    <section>
        <h2 class="text-lg font-semibold text-white mb-3">9. Contact</h2>
        <p>
            Privacy questions? Email <a href="mailto:{{ config('legal.support_email') }}" class="text-violet-400 hover:underline">{{ config('legal.support_email') }}</a>
            or use our <a href="{{ route('support') }}" class="text-violet-400 hover:underline">support page</a>.
        </p>
    </section>
@endsection
