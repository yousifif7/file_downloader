<?php

namespace App\Http\Controllers;

use App\Models\SupportTicket;
use App\Services\SupportChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupportController extends Controller
{
    public function landing(): View|RedirectResponse
    {
        if (auth()->check()) {
            return redirect()->route('support.tickets.index');
        }

        return view('pages.support');
    }

    public function index(Request $request): View
    {
        $tickets = SupportTicket::query()
            ->where('user_id', $request->user()->id)
            ->withCount('messages')
            ->latest('last_message_at')
            ->latest()
            ->paginate(20);

        return view('support.tickets.index', [
            'tickets' => $tickets,
        ]);
    }

    public function create(Request $request): View
    {
        return view('support.tickets.create', [
            'downloadId' => $request->integer('download') ?: null,
        ]);
    }

    public function store(Request $request, SupportChatService $supportChat): RedirectResponse
    {
        $this->throttle($request, 'support-open');

        $validated = $request->validate([
            'subject' => ['required', 'string', Rule::in([
                'Download failed',
                'Billing or subscription',
                'Refund request',
                'Account access',
                'Other',
            ])],
            'download_id' => ['nullable', 'integer', 'min:1'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $ticket = $supportChat->openTicket($request->user(), $validated);

        return redirect()
            ->route('support.tickets.show', $ticket)
            ->with('status', 'Your support conversation has been opened.');
    }

    public function show(Request $request, SupportTicket $supportTicket): View
    {
        abort_unless($supportTicket->user_id === $request->user()->id, 403);

        $supportTicket->load(['messages.user', 'messages.staffUser', 'download.platform']);
        $supportTicket->markReadByUser();

        return view('support.tickets.show', [
            'ticket' => $supportTicket,
        ]);
    }

    public function reply(Request $request, SupportTicket $supportTicket, SupportChatService $supportChat): RedirectResponse
    {
        abort_unless($supportTicket->user_id === $request->user()->id, 403);
        $this->throttle($request, 'support-reply:'.$supportTicket->id);

        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $supportChat->addUserMessage($supportTicket, $request->user(), $validated['message']);

        return redirect()
            ->route('support.tickets.show', $supportTicket)
            ->with('status', 'Message sent.');
    }

    private function throttle(Request $request, string $key): void
    {
        $rateKey = $key.':'.$request->user()->id;

        if (RateLimiter::tooManyAttempts($rateKey, 10)) {
            $seconds = RateLimiter::availableIn($rateKey);
            abort(429, "Too many messages. Please wait {$seconds} seconds.");
        }

        RateLimiter::hit($rateKey, 3600);
    }
}
