<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Announcement;
use App\Models\Tenant\AnnouncementRead;
use App\Models\Tenant\Poll;
use App\Models\Tenant\PollVote;
use App\Services\MailService;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    /* ================================================================
     | INDEX
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $tab    = $request->get('tab', 'announcements');
        $status = $request->get('status', 'all');

        $query = Announcement::with('creator')->latest();
        if ($status !== 'all') $query->where('status', $status);
        if ($from = $request->get('from')) {
            $query->whereDate('publish_at', '>=', $from);
        }
        if ($to = $request->get('to')) {
            $query->whereDate('publish_at', '<=', $to);
        }

        $announcements = $query->paginate(12)->withQueryString();

        $polls = Poll::with(['creator','votes'])
            ->latest()->paginate(10, ['*'], 'polls_page')->withQueryString();

        $stats = [
            'published'  => Announcement::where('status','published')->count(),
            'scheduled'  => Announcement::where('status','scheduled')->count(),
            'drafts'     => Announcement::where('status','draft')->count(),
            'active_polls'=> Poll::where('status','active')->count(),
        ];

        return view('admin.announcements.index', compact(
            'tab', 'announcements', 'polls', 'stats', 'status', 'tenant'
        ));
    }

    /* ================================================================
     | STORE ANNOUNCEMENT
     |================================================================*/
    public function store(string $tenant, Request $request)
    {
        $data = $request->validate([
            'title'                   => 'required|string|max:200',
            'content'                 => 'required|string',
            'priority'                => 'required|in:low,normal,high,urgent',
            'audience'                => 'required|in:all,department,location,role,specific',
            'status'                  => 'required|in:draft,published,scheduled',
            'publish_at'              => 'nullable|date',
            'expires_at'              => 'nullable|date',
            'requires_acknowledgment' => 'boolean',
            'show_on_login'           => 'boolean',
            'audience_filter'         => 'nullable|array',
            'banner_image'            => 'nullable|image|max:4096',
        ]);

        if ($request->hasFile('banner_image')) {
            $data['banner_image'] = $request->file('banner_image')
                ->store('announcements/banners', 'public');
        }

        $data['created_by'] = auth()->id();

        if ($data['status'] === 'published' && empty($data['publish_at'])) {
            $data['publish_at'] = now();
        }
        if ($data['status'] === 'scheduled' && ! empty($data['publish_at'])) {
            $data['status'] = 'scheduled';
        }

        $announcement = Announcement::create($data);

        if ($announcement->status === 'published') {
            $this->dispatchAnnouncement($tenant, $announcement);
        }

        return back()->with('success', 'Announcement created.');
    }

    /**
     * Notify (bell + push, via NotificationService) and email every user
     * targeted by this announcement's audience/audience_filter. Called once,
     * the moment an announcement first becomes 'published' — from store()
     * directly, or from update() when a draft/scheduled announcement is
     * edited into 'published'.
     */
    private function dispatchAnnouncement(string $tenant, Announcement $announcement): void
    {
        $targets = $announcement->targetUsers();

        $icon  = match ($announcement->priority) {
            'urgent' => 'alert-triangle',
            'high'   => 'alert-circle',
            default  => 'megaphone',
        };
        $color = match ($announcement->priority) {
            'urgent' => '#EF4444',
            'high'   => '#F59E0B',
            default  => '#6C7DF7',
        };
        $label = $announcement->priority === 'urgent' ? 'Urgent Announcement' : 'New Announcement';

        foreach ($targets as $user) {
            try {
                NotificationService::send(
                    $user, $announcement->title, $label,
                    $icon, $color,
                    route('employee.announcements.show', [$tenant, $announcement->id])
                );
            } catch (\Throwable $e) {
                \Log::error('Announcement notification failed: '.$e->getMessage());
            }
        }

        try {
            $emails = $targets->pluck('email')->filter()->unique()->values()->toArray();
            app(MailService::class)->sendAnnouncement($announcement, $emails);
        } catch (\Throwable $e) {
            \Log::error('Announcement email failed: '.$e->getMessage());
        }
    }

    /* ================================================================
     | UPDATE
     |================================================================*/
    public function update(string $tenant, Request $request, Announcement $announcement)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:200',
            'content'    => 'required|string',
            'priority'   => 'required|in:low,normal,high,urgent',
            'status'     => 'required|in:draft,published,scheduled,archived',
            'expires_at' => 'nullable|date',
        ]);

        $wasPublished = $announcement->status === 'published';

        if ($data['status'] === 'published' && empty($announcement->publish_at)) {
            $data['publish_at'] = now();
        }

        $announcement->update($data);

        // Only notify the first time it becomes published — editing an
        // already-published announcement again shouldn't re-notify everyone.
        if (! $wasPublished && $announcement->status === 'published') {
            $this->dispatchAnnouncement($tenant, $announcement);
        }

        return back()->with('success', 'Announcement updated.');
    }

    /* ================================================================
     | DESTROY
     |================================================================*/
    public function destroy(string $tenant, Announcement $announcement)
    {
        if ($announcement->banner_image) {
            Storage::disk('public')->delete($announcement->banner_image);
        }
        $announcement->delete();
        return back()->with('success', 'Announcement deleted.');
    }

    /* ================================================================
     | MARK AS READ
     |================================================================*/
    public function markRead(string $tenant, Announcement $announcement)
    {
        AnnouncementRead::updateOrCreate(
            ['announcement_id' => $announcement->id, 'user_id' => auth()->id()],
            ['read_at' => now()]
        );
        $announcement->increment('views_count');
        return response()->json(['success' => true]);
    }

    /* ================================================================
     | POLLS — STORE
     |================================================================*/
    public function storePoll(string $tenant, Request $request)
    {
        $data = $request->validate([
            'question'     => 'required|string|max:500',
            'description'  => 'nullable|string',
            'type'         => 'required|in:single_choice,multiple_choice,rating',
            'options'      => 'required|array|min:2',
            'options.*'    => 'required|string|max:200',
            'is_anonymous' => 'boolean',
            'starts_at'    => 'nullable|date',
            'ends_at'      => 'nullable|date|after:starts_at',
            'status'       => 'required|in:draft,active',
        ]);

        $data['created_by'] = auth()->id();
        $data['options']    = array_values(array_filter($data['options']));

        Poll::create($data);
        return back()->with('success', 'Poll created.');
    }

    /* ================================================================
     | POLLS — VOTE
     |================================================================*/
    public function vote(string $tenant, Request $request, Poll $poll)
    {
        $request->validate([
            'selected_options'   => 'required|array|min:1',
            'selected_options.*' => 'integer|min:0',
        ]);

        if (! $poll->is_active) {
            return back()->with('error', 'This poll is no longer active.');
        }

        PollVote::updateOrCreate(
            ['poll_id' => $poll->id, 'user_id' => auth()->id()],
            ['selected_options' => $request->selected_options]
        );

        return back()->with('success', 'Your vote has been recorded.');
    }

    /* ================================================================
     | POLLS — CLOSE
     |================================================================*/
    public function closePoll(string $tenant, Poll $poll)
    {
        $poll->update(['status' => 'closed']);
        return back()->with('success', 'Poll closed.');
    }

    /* ================================================================
     | POLLS — DESTROY
     |================================================================*/
    public function destroyPoll(string $tenant, Poll $poll)
    {
        $poll->delete();
        return back()->with('success', 'Poll deleted.');
    }
}