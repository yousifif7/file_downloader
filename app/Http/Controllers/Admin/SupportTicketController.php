<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\SupportChatService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();

        $tickets = SupportTicket::query()
            ->with(['user', 'download'])
            ->withCount('messages')
            ->when($status === 'open', fn ($query) => $query->where('status', SupportTicket::STATUS_OPEN))
            ->when($status === 'closed', fn ($query) => $query->where('status', SupportTicket::STATUS_CLOSED))
            ->when($status === 'unread', fn ($query) => $query->where('awaiting_staff', true))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = '%'.$request->string('search').'%';
                $query->where(function ($inner) use ($search): void {
                    $inner->where('subject', 'like', $search)
                        ->orWhereHas('user', function ($userQuery) use ($search): void {
                            $userQuery->where('name', 'like', $search)
                                ->orWhere('email', 'like', $search);
                        });
                });
            })
            ->latest('last_message_at')
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $unreadCount = SupportTicket::query()
            ->where('status', SupportTicket::STATUS_OPEN)
            ->where('awaiting_staff', true)
            ->count();

        return view('admin.support-tickets.index', [
            'tickets' => $tickets,
            'search' => $request->string('search')->toString(),
            'status' => $status,
            'unreadCount' => $unreadCount,
        ]);
    }

    public function show(SupportTicket $supportTicket): View
    {
        $supportTicket->load(['user', 'download.platform', 'messages.user', 'messages.staffUser']);
        $supportTicket->markReadByStaff();

        return view('admin.support-tickets.show', [
            'ticket' => $supportTicket,
        ]);
    }

    public function reply(Request $request, SupportTicket $supportTicket, SupportChatService $supportChat): RedirectResponse
    {
        $validated = $request->validate([
            'message' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $supportChat->addStaffMessage($supportTicket, $request->user(), $validated['message']);

        return redirect()
            ->route('admin.support-tickets.show', $supportTicket)
            ->with('status', 'Reply sent to the customer.');
    }

    public function close(SupportTicket $supportTicket, SupportChatService $supportChat): RedirectResponse
    {
        $supportChat->closeTicket($supportTicket);

        return back()->with('status', 'Conversation closed.');
    }

    public function reopen(SupportTicket $supportTicket, SupportChatService $supportChat): RedirectResponse
    {
        $supportChat->reopenTicket($supportTicket);

        return back()->with('status', 'Conversation reopened.');
    }
}
