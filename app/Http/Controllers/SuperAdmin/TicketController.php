<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Main\SupportTicket;
use App\Models\Main\SupportTicketReply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $query = SupportTicket::with(['tenant', 'assignee'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q->where('subject', 'like', "%{$search}%")
                ->orWhere('ticket_number', 'like', "%{$search}%")
                ->orWhereHas('tenant', fn($t) => $t->where('company_name', 'like', "%{$search}%")));
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $tickets = $query->paginate(20)->withQueryString();

        $statusCounts = SupportTicket::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $stats = [
            'open'       => SupportTicket::where('status', 'open')->count(),
            'in_progress'=> SupportTicket::where('status', 'in_progress')->count(),
            'resolved'   => SupportTicket::where('status', 'resolved')->count(),
            'urgent'     => SupportTicket::where('priority', 'urgent')->whereNotIn('status', ['resolved','closed'])->count(),
        ];

        return view('superadmin.tickets.index', compact('tickets', 'statusCounts', 'stats'));
    }

    public function show(SupportTicket $ticket)
    {
        $ticket->load(['tenant', 'assignee', 'replies' => fn($q) => $q->orderBy('created_at')]);

        $agents = \App\Models\Main\SuperAdmin::orderBy('name')->get(['id', 'name', 'email']);

        return view('superadmin.tickets.show', compact('ticket', 'agents'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'message'          => 'required|string|max:5000',
            'is_internal_note' => 'boolean',
        ]);

        $agent = Auth::guard('superadmin')->user();

        $ticket->replies()->create([
            'author_type'      => get_class($agent),
            'author_id'        => $agent->id,
            'message'          => $data['message'],
            'is_internal_note' => $request->boolean('is_internal_note'),
        ]);

        if (!$ticket->first_response_at && !$request->boolean('is_internal_note')) {
            $ticket->update(['first_response_at' => now()]);
        }

        if ($ticket->status === 'open') {
            $ticket->update(['status' => 'in_progress']);
        }

        return back()->with('success', 'Reply sent successfully.');
    }

    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'status' => 'required|in:open,in_progress,waiting_on_customer,resolved,closed',
        ]);

        $updates = ['status' => $data['status']];

        if ($data['status'] === 'resolved' && !$ticket->resolved_at) {
            $updates['resolved_at'] = now();
        }
        if ($data['status'] === 'closed' && !$ticket->closed_at) {
            $updates['closed_at'] = now();
        }

        $ticket->update($updates);

        return back()->with('success', 'Ticket status updated to ' . ucfirst(str_replace('_', ' ', $data['status'])) . '.');
    }

    public function assign(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'assigned_to' => 'nullable|exists:main.super_admins,id',
        ]);

        $ticket->update(['assigned_to' => $data['assigned_to']]);

        return back()->with('success', 'Ticket assignment updated.');
    }
}
