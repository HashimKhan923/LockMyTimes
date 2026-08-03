<?php

namespace App\Http\Controllers\Api\V1\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\AnnouncementResource;
use App\Http\Resources\PollResource;
use App\Models\Tenant\Announcement;
use App\Models\Tenant\AnnouncementRead;
use App\Models\Tenant\Poll;
use App\Models\Tenant\PollVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * JSON mirror of Employee/AnnouncementController — same audience-visibility
 * rules, same read/acknowledge tracking, same poll voting/results logic.
 */
class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $emp = $request->user()->employee;
        $user = $request->user();
        $filter = $request->get('filter', 'all');

        $announcements = $this->visibleAnnouncementsQuery($emp, $user)
            ->with('creator')
            ->orderByRaw("FIELD(priority, 'urgent','high','normal','low')")
            ->orderByDesc('publish_at')
            ->orderByDesc('created_at')
            ->paginate(15);

        $readIds = AnnouncementRead::where('user_id', $user->id)
            ->whereIn('announcement_id', $announcements->pluck('id'))
            ->get()
            ->keyBy('announcement_id');

        $announcements->getCollection()->transform(function ($a) use ($readIds) {
            $r = $readIds->get($a->id);
            $a->_is_read = (bool) $r;
            $a->_is_acknowledged = (bool) ($r && $r->acknowledged_at);
            $a->_acknowledged_at = $r?->acknowledged_at?->toIso8601String();
            $a->_needs_action = $a->requires_acknowledgment && ! $a->_is_acknowledged;

            return $a;
        });

        if ($filter === 'unread') {
            $announcements->setCollection($announcements->getCollection()->filter(fn ($a) => ! $a->_is_read)->values());
        } elseif ($filter === 'acknowledgments') {
            $announcements->setCollection($announcements->getCollection()->filter(fn ($a) => $a->requires_acknowledgment)->values());
        }

        $counters = $this->buildCounters($emp, $user);

        $activePolls = Poll::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        $myVotes = PollVote::where('user_id', $user->id)->whereIn('poll_id', $activePolls->pluck('id'))->pluck('selected_options', 'poll_id');
        $activePolls->each(function ($p) use ($myVotes) {
            $p->_has_voted = $myVotes->has($p->id);
        });

        return response()->json([
            'announcements' => AnnouncementResource::collection($announcements->getCollection()),
            'pagination' => ['current_page' => $announcements->currentPage(), 'last_page' => $announcements->lastPage(), 'total' => $announcements->total()],
            'counters' => $counters,
            'active_polls' => PollResource::collection($activePolls),
        ]);
    }

    public function pollsIndex(Request $request): JsonResponse
    {
        $emp = $request->user()->employee;
        $user = $request->user();

        $polls = Poll::with('creator')
            ->where('status', '!=', 'draft')
            ->orderByRaw("FIELD(status, 'active','closed') ASC")
            ->orderByDesc('created_at')
            ->paginate(12);

        $myVotes = PollVote::where('user_id', $user->id)->whereIn('poll_id', $polls->pluck('id'))->get()->keyBy('poll_id');

        $polls->getCollection()->transform(function ($p) use ($myVotes) {
            $vote = $myVotes->get($p->id);
            $p->_has_voted = (bool) $vote;
            $p->_my_choices = $vote ? $vote->selected_options : [];
            $p->_is_active = $this->pollIsActive($p);

            return $p;
        });

        return response()->json([
            'polls' => PollResource::collection($polls->getCollection()),
            'pagination' => ['current_page' => $polls->currentPage(), 'last_page' => $polls->lastPage(), 'total' => $polls->total()],
            'counters' => $this->buildCounters($emp, $user),
        ]);
    }

    public function show(Request $request, int $announcement): JsonResponse
    {
        $emp = $request->user()->employee;
        $user = $request->user();

        $a = Announcement::with('creator')->findOrFail($announcement);
        abort_unless($this->canViewAnnouncement($a, $emp, $user), 403, 'You do not have access to this announcement.');

        $read = AnnouncementRead::firstOrCreate(
            ['announcement_id' => $a->id, 'user_id' => $user->id],
            ['read_at' => now()]
        );

        if ($read->wasRecentlyCreated) {
            $a->increment('views_count');
        }

        $a->_is_read = true;
        $a->_is_acknowledged = (bool) $read->acknowledged_at;
        $a->_acknowledged_at = $read->acknowledged_at?->toIso8601String();
        $a->_needs_action = $a->requires_acknowledgment && ! $a->_is_acknowledged;

        return response()->json(['announcement' => new AnnouncementResource($a)]);
    }

    public function markRead(Request $request, int $announcement): JsonResponse
    {
        $emp = $request->user()->employee;
        $user = $request->user();

        $a = Announcement::findOrFail($announcement);
        abort_unless($this->canViewAnnouncement($a, $emp, $user), 403);

        $read = AnnouncementRead::firstOrCreate(
            ['announcement_id' => $a->id, 'user_id' => $user->id],
            ['read_at' => now()]
        );

        if ($read->wasRecentlyCreated) {
            $a->increment('views_count');
        }

        return response()->json(['success' => true]);
    }

    public function acknowledge(Request $request, int $announcement): JsonResponse
    {
        $emp = $request->user()->employee;
        $user = $request->user();

        $a = Announcement::findOrFail($announcement);
        abort_unless($this->canViewAnnouncement($a, $emp, $user), 403);

        if (! $a->requires_acknowledgment) {
            return response()->json(['success' => false, 'message' => 'This announcement does not require acknowledgment.'], 422);
        }

        $read = AnnouncementRead::firstOrCreate(
            ['announcement_id' => $a->id, 'user_id' => $user->id],
            ['read_at' => now()]
        );

        if (! $read->acknowledged_at) {
            $read->update(['acknowledged_at' => now()]);
        }

        return response()->json(['success' => true, 'message' => 'Acknowledged. Thanks for confirming.']);
    }

    public function votePoll(Request $request, int $poll): JsonResponse
    {
        $user = $request->user();
        $p = Poll::findOrFail($poll);

        if (! $this->pollIsActive($p)) {
            return response()->json(['success' => false, 'message' => 'This poll is no longer active.'], 422);
        }

        $data = $request->validate([
            'selected_options' => ['required', 'array', 'min:1'],
            'selected_options.*' => ['integer', 'min:0'],
        ]);

        $optionsCount = count($p->options ?? []);
        foreach ($data['selected_options'] as $idx) {
            if ($idx < 0 || $idx >= $optionsCount) {
                return response()->json(['success' => false, 'message' => 'Invalid choice.'], 422);
            }
        }

        if ($p->type === 'single_choice' && count($data['selected_options']) > 1) {
            return response()->json(['success' => false, 'message' => 'You can only choose one option in this poll.'], 422);
        }

        $choices = array_values(array_unique(array_map('intval', $data['selected_options'])));

        PollVote::updateOrCreate(['poll_id' => $p->id, 'user_id' => $user->id], ['selected_options' => $choices]);

        return response()->json(['success' => true, 'message' => 'Your vote has been recorded.']);
    }

    public function showPoll(Request $request, int $poll): JsonResponse
    {
        $user = $request->user();
        $p = Poll::with('creator')->findOrFail($poll);

        abort_if($p->status === 'draft', 404);

        $myVote = PollVote::where('poll_id', $p->id)->where('user_id', $user->id)->first();
        $hasVoted = (bool) $myVote;
        $isActive = $this->pollIsActive($p);

        $totalVotes = PollVote::where('poll_id', $p->id)->count();
        $options = $p->options ?? [];

        $results = [];
        foreach ($options as $idx => $option) {
            $label = is_array($option) ? ($option['text'] ?? $option['label'] ?? (string) $idx) : (string) $option;
            $count = PollVote::where('poll_id', $p->id)->whereJsonContains('selected_options', (int) $idx)->count();
            $results[] = [
                'index' => $idx,
                'option' => $label,
                'votes' => $count,
                'percent' => $totalVotes > 0 ? round($count / $totalVotes * 100) : 0,
            ];
        }

        $p->_has_voted = $hasVoted;
        $p->_my_choices = $myVote ? $myVote->selected_options : [];
        $p->_is_active = $isActive;

        return response()->json([
            'poll' => new PollResource($p),
            'total_votes' => $totalVotes,
            'results' => $results,
            'show_results' => $hasVoted || ! $isActive,
        ]);
    }

    protected function visibleAnnouncementsQuery($emp, $user)
    {
        $query = Announcement::query()
            ->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('publish_at')->orWhere('publish_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));

        $query->where(function ($q) use ($emp, $user) {
            $q->where('audience', 'all');

            if ($emp && $emp->department_id) {
                $q->orWhere(function ($q2) use ($emp) {
                    $q2->where('audience', 'department')
                        ->whereJsonContains('audience_filter', (int) $emp->department_id)
                        ->orWhere(fn ($q3) => $q3->where('audience', 'department')->whereJsonContains('audience_filter', (string) $emp->department_id));
                });
            }

            if ($emp && $emp->location_id) {
                $q->orWhere(function ($q2) use ($emp) {
                    $q2->where('audience', 'location')
                        ->whereJsonContains('audience_filter', (int) $emp->location_id)
                        ->orWhere(fn ($q3) => $q3->where('audience', 'location')->whereJsonContains('audience_filter', (string) $emp->location_id));
                });
            }

            if (method_exists($user, 'getRoleNames')) {
                $roles = $user->getRoleNames()->toArray();
                if (! empty($roles)) {
                    $q->orWhere(function ($q2) use ($roles) {
                        $q2->where('audience', 'role')->where(function ($q3) use ($roles) {
                            foreach ($roles as $role) {
                                $q3->orWhereJsonContains('audience_filter', $role);
                            }
                        });
                    });
                }
            }

            $q->orWhere(function ($q2) use ($user) {
                $q2->where('audience', 'specific')
                    ->where(fn ($q3) => $q3->whereJsonContains('audience_filter', (int) $user->id)->orWhereJsonContains('audience_filter', (string) $user->id));
            });
        });

        return $query;
    }

    protected function canViewAnnouncement(Announcement $a, $emp, $user): bool
    {
        if ($a->status !== 'published') return false;
        if ($a->publish_at && $a->publish_at->isFuture()) return false;
        if ($a->expires_at && $a->expires_at->isPast()) return false;

        $filter = (array) ($a->audience_filter ?? []);

        return match ($a->audience) {
            'all' => true,
            'department' => $emp && $emp->department_id && (in_array($emp->department_id, $filter) || in_array((string) $emp->department_id, $filter)),
            'location' => $emp && $emp->location_id && (in_array($emp->location_id, $filter) || in_array((string) $emp->location_id, $filter)),
            'role' => method_exists($user, 'hasAnyRole') && $user->hasAnyRole($filter),
            'specific' => in_array($user->id, $filter) || in_array((string) $user->id, $filter),
            default => false,
        };
    }

    protected function pollIsActive(Poll $p): bool
    {
        if ($p->status !== 'active') return false;
        if ($p->starts_at && $p->starts_at->isFuture()) return false;
        if ($p->ends_at && $p->ends_at->isPast()) return false;

        return true;
    }

    protected function buildCounters($emp, $user): array
    {
        $allIds = $this->visibleAnnouncementsQuery($emp, $user)->pluck('id');

        $total = $allIds->count();
        $readRows = AnnouncementRead::where('user_id', $user->id)->whereIn('announcement_id', $allIds)->get();
        $readCount = $readRows->count();
        $unread = $total - $readCount;

        $needAck = Announcement::whereIn('id', $allIds)
            ->where('requires_acknowledgment', true)
            ->whereNotIn('id', $readRows->whereNotNull('acknowledged_at')->pluck('announcement_id'))
            ->count();

        $activePolls = Poll::where('status', 'active')
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->count();

        return [
            'total' => $total,
            'unread' => $unread,
            'needs_ack' => $needAck,
            'active_polls' => $activePolls,
        ];
    }
}
