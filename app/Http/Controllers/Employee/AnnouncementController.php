<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Announcement;
use App\Models\Tenant\AnnouncementRead;
use App\Models\Tenant\Poll;
use App\Models\Tenant\PollVote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnnouncementController extends Controller
{
    /* ================================================================
     | INDEX — News feed
     |================================================================*/
    public function index(string $tenant, Request $request)
    {
        $emp  = auth()->user()->employee;
        $user = auth()->user();

        $filter = $request->get('filter', 'all'); // all|unread|acknowledgments|polls

        if ($filter === 'polls') {
            return $this->pollsIndex($tenant, $request);
        }

        // Visible announcements
        $announcements = $this->visibleAnnouncementsQuery($emp, $user)
            ->with('creator')
            ->orderByRaw("FIELD(priority, 'urgent','high','normal','low')")
            ->orderByDesc('publish_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        // Read IDs
        $readIds = AnnouncementRead::where('user_id', $user->id)
            ->whereIn('announcement_id', $announcements->pluck('id'))
            ->get()
            ->keyBy('announcement_id');

        // Attach read state
        $announcements->getCollection()->transform(function ($a) use ($readIds) {
            $r = $readIds->get($a->id);
            $a->_is_read = (bool) $r;
            $a->_is_acknowledged = (bool) ($r && $r->acknowledged_at);
            $a->_needs_action = $a->requires_acknowledgment && ! $a->_is_acknowledged;
            return $a;
        });

        // Apply filter
        if ($filter === 'unread') {
            $announcements->setCollection(
                $announcements->getCollection()->filter(fn ($a) => ! $a->_is_read)->values()
            );
        } elseif ($filter === 'acknowledgments') {
            $announcements->setCollection(
                $announcements->getCollection()->filter(fn ($a) => $a->requires_acknowledgment)->values()
            );
        }

        // Counters
        $counters = $this->buildCounters($emp, $user);

        // Active polls for the right sidebar
        $activePolls = Poll::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->limit(3)
            ->get();

        // Attach voted state to active polls
        $myVotes = PollVote::where('user_id', $user->id)
            ->whereIn('poll_id', $activePolls->pluck('id'))
            ->pluck('selected_options', 'poll_id');

        $activePolls->each(function ($p) use ($myVotes) {
            $p->_has_voted = $myVotes->has($p->id);
        });

        return view('employee.announcements.index', [
            'tenantSlug'    => $tenant,
            'emp'           => $emp,
            'announcements' => $announcements,
            'counters'      => $counters,
            'filter'        => $filter,
            'activePolls'   => $activePolls,
        ]);
    }

    /* ================================================================
     | POLLS INDEX (tab)
     |================================================================*/
    protected function pollsIndex(string $tenant, Request $request)
    {
        $emp  = auth()->user()->employee;
        $user = auth()->user();

        $polls = Poll::with('creator')
            ->where('status', '!=', 'draft')
            ->orderByRaw("FIELD(status, 'active','closed') ASC")
            ->orderByDesc('created_at')
            ->paginate(12)
            ->withQueryString();

        // Attach my vote info
        $myVotes = PollVote::where('user_id', $user->id)
            ->whereIn('poll_id', $polls->pluck('id'))
            ->get()
            ->keyBy('poll_id');

        $polls->getCollection()->transform(function ($p) use ($myVotes) {
            $vote = $myVotes->get($p->id);
            $p->_has_voted = (bool) $vote;
            $p->_my_choices = $vote ? $vote->selected_options : [];
            $p->_is_active = $this->pollIsActive($p);
            return $p;
        });

        $counters = $this->buildCounters($emp, $user);

        return view('employee.announcements.polls', [
            'tenantSlug' => $tenant,
            'emp'        => $emp,
            'polls'      => $polls,
            'counters'   => $counters,
            'filter'     => 'polls',
        ]);
    }

    /* ================================================================
     | SHOW — Announcement detail
     |================================================================*/
    public function show(string $tenant, int $announcement)
    {
        $emp  = auth()->user()->employee;
        $user = auth()->user();

        $a = Announcement::with('creator')->findOrFail($announcement);

        // Access control: must be visible to this user
        abort_unless($this->canViewAnnouncement($a, $emp, $user), 403, 'You do not have access to this announcement.');

        // Auto-mark as read on first view
        $read = AnnouncementRead::firstOrCreate(
            ['announcement_id' => $a->id, 'user_id' => $user->id],
            ['read_at' => now()]
        );

        if ($read->wasRecentlyCreated) {
            $a->increment('views_count');
        }

        $isAcknowledged = (bool) $read->acknowledged_at;

        return view('employee.announcements.show', [
            'tenantSlug'     => $tenant,
            'emp'            => $emp,
            'announcement'   => $a,
            'isAcknowledged' => $isAcknowledged,
            'read'           => $read,
        ]);
    }

    /* ================================================================
     | MARK READ (AJAX)
     |================================================================*/
    public function markRead(string $tenant, int $announcement)
    {
        $emp  = auth()->user()->employee;
        $user = auth()->user();

        $a = Announcement::findOrFail($announcement);
        abort_unless($this->canViewAnnouncement($a, $emp, $user), 403);

        $read = AnnouncementRead::firstOrCreate(
            ['announcement_id' => $a->id, 'user_id' => $user->id],
            ['read_at' => now()]
        );

        if ($read->wasRecentlyCreated) {
            $a->increment('views_count');
        }

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }
        return back();
    }

    /* ================================================================
     | ACKNOWLEDGE
     |================================================================*/
    public function acknowledge(string $tenant, int $announcement)
    {
        $emp  = auth()->user()->employee;
        $user = auth()->user();

        $a = Announcement::findOrFail($announcement);
        abort_unless($this->canViewAnnouncement($a, $emp, $user), 403);

        if (! $a->requires_acknowledgment) {
            return back()->with('error', 'This announcement does not require acknowledgment.');
        }

        $read = AnnouncementRead::firstOrCreate(
            ['announcement_id' => $a->id, 'user_id' => $user->id],
            ['read_at' => now()]
        );

        if (! $read->acknowledged_at) {
            $read->update(['acknowledged_at' => now()]);
        }

        return back()->with('success', 'Acknowledged. Thanks for confirming.');
    }

    /* ================================================================
     | POLLS — VOTE
     |================================================================*/
    public function votePoll(string $tenant, Request $request, int $poll)
    {
        $user = auth()->user();

        $p = Poll::findOrFail($poll);

        if (! $this->pollIsActive($p)) {
            return back()->with('error', 'This poll is no longer active.');
        }

        $data = $request->validate([
            'selected_options'   => ['required', 'array', 'min:1'],
            'selected_options.*' => ['integer', 'min:0'],
        ]);

        $optionsCount = count($p->options ?? []);
        foreach ($data['selected_options'] as $idx) {
            if ($idx < 0 || $idx >= $optionsCount) {
                return back()->with('error', 'Invalid choice.');
            }
        }

        if ($p->type === 'single_choice' && count($data['selected_options']) > 1) {
            return back()->with('error', 'You can only choose one option in this poll.');
        }

        // Dedupe
        $choices = array_values(array_unique(array_map('intval', $data['selected_options'])));

        PollVote::updateOrCreate(
            ['poll_id' => $p->id, 'user_id' => $user->id],
            ['selected_options' => $choices]
        );

        return back()->with('success', 'Your vote has been recorded.');
    }

    /* ================================================================
     | POLLS — Show results
     |================================================================*/
    public function showPoll(string $tenant, int $poll)
    {
        $user = auth()->user();
        $emp  = auth()->user()->employee;

        $p = Poll::with('creator')->findOrFail($poll);

        if ($p->status === 'draft') {
            abort(404);
        }

        $myVote = PollVote::where('poll_id', $p->id)
            ->where('user_id', $user->id)
            ->first();

        $hasVoted = (bool) $myVote;
        $myChoices = $myVote ? $myVote->selected_options : [];

        $isActive = $this->pollIsActive($p);

        // Build results — by INDEX (not label, to handle duplicates)
        $totalVotes = PollVote::where('poll_id', $p->id)->count();
        $options = $p->options ?? [];

        $results = [];
        foreach ($options as $idx => $option) {
            $label = is_array($option) ? ($option['text'] ?? $option['label'] ?? (string) $idx) : (string) $option;
            $count = PollVote::where('poll_id', $p->id)
                ->whereJsonContains('selected_options', (int) $idx)
                ->count();
            $results[] = [
                'index'   => $idx,
                'option'  => $label,
                'votes'   => $count,
                'percent' => $totalVotes > 0 ? round($count / $totalVotes * 100) : 0,
            ];
        }

        // Should we show results? Only if voted or poll closed
        $showResults = $hasVoted || ! $isActive;

        return view('employee.announcements.poll-show', [
            'tenantSlug'  => $tenant,
            'emp'         => $emp,
            'poll'        => $p,
            'hasVoted'    => $hasVoted,
            'myChoices'   => $myChoices,
            'isActive'    => $isActive,
            'totalVotes'  => $totalVotes,
            'results'     => $results,
            'showResults' => $showResults,
        ]);
    }

    /* ================================================================
     | HELPERS
     |================================================================*/

    /**
     * Query for announcements visible to this employee.
     */
    protected function visibleAnnouncementsQuery($emp, $user)
    {
        $query = Announcement::query()
            // 'scheduled' announcements never flip to 'published' on their own (no cron job does
            // that transition) — treat a scheduled announcement whose publish_at has passed as
            // visible too, otherwise it stays invisible forever past its intended publish date.
            ->where(function ($q) {
                $q->where('status', 'published')
                    ->orWhere(function ($q2) {
                        $q2->where('status', 'scheduled')->whereNotNull('publish_at')->where('publish_at', '<=', now());
                    });
            })
            ->where(function ($q) {
                $q->whereNull('publish_at')->orWhere('publish_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });

        // Audience filter (must be visible to current user)
        $query->where(function ($q) use ($emp, $user) {
            // Audience: all
            $q->where('audience', 'all');

            // Audience: department
            if ($emp && $emp->department_id) {
                $q->orWhere(function ($q2) use ($emp) {
                    $q2->where('audience', 'department')
                       ->whereJsonContains('audience_filter', (int) $emp->department_id)
                       ->orWhere(function ($q3) use ($emp) {
                           $q3->where('audience', 'department')
                              ->whereJsonContains('audience_filter', (string) $emp->department_id);
                       });
                });
            }

            // Audience: location
            if ($emp && $emp->location_id) {
                $q->orWhere(function ($q2) use ($emp) {
                    $q2->where('audience', 'location')
                       ->whereJsonContains('audience_filter', (int) $emp->location_id)
                       ->orWhere(function ($q3) use ($emp) {
                           $q3->where('audience', 'location')
                              ->whereJsonContains('audience_filter', (string) $emp->location_id);
                       });
                });
            }

            // Audience: role
            if (method_exists($user, 'getRoleNames')) {
                $roles = $user->getRoleNames()->toArray();
                if (! empty($roles)) {
                    $q->orWhere(function ($q2) use ($roles) {
                        $q2->where('audience', 'role')
                           ->where(function ($q3) use ($roles) {
                               foreach ($roles as $role) {
                                   $q3->orWhereJsonContains('audience_filter', $role);
                               }
                           });
                    });
                }
            }

            // Audience: specific
            $q->orWhere(function ($q2) use ($user) {
                $q2->where('audience', 'specific')
                   ->where(function ($q3) use ($user) {
                       $q3->whereJsonContains('audience_filter', (int) $user->id)
                          ->orWhereJsonContains('audience_filter', (string) $user->id);
                   });
            });
        });

        return $query;
    }

    protected function canViewAnnouncement(Announcement $a, $emp, $user): bool
    {
        // Not yet published or expired or draft
        if ($a->status !== 'published') return false;
        if ($a->publish_at && $a->publish_at->isFuture()) return false;
        if ($a->expires_at && $a->expires_at->isPast()) return false;

        $filter = (array) ($a->audience_filter ?? []);

        return match ($a->audience) {
            'all'        => true,
            'department' => $emp && $emp->department_id
                            && (in_array($emp->department_id, $filter)
                                || in_array((string) $emp->department_id, $filter)),
            'location'   => $emp && $emp->location_id
                            && (in_array($emp->location_id, $filter)
                                || in_array((string) $emp->location_id, $filter)),
            'role'       => $this->userHasAnyRole($user, $filter),
            'specific'   => in_array($user->id, $filter)
                            || in_array((string) $user->id, $filter),
            default      => false,
        };
    }

    protected function userHasAnyRole($user, array $roleNames): bool
    {
        if (! method_exists($user, 'hasAnyRole')) return false;
        return $user->hasAnyRole($roleNames);
    }

    protected function pollIsActive(Poll $p): bool
    {
        if ($p->status !== 'active') return false;
        if ($p->starts_at && $p->starts_at->isFuture()) return false;
        if ($p->ends_at && $p->ends_at->isPast()) return false;
        return true;
    }

    protected function buildCounters($emp, $user): object
    {
        $allIds = $this->visibleAnnouncementsQuery($emp, $user)->pluck('id');

        $total = $allIds->count();
        $readIds = AnnouncementRead::where('user_id', $user->id)
            ->whereIn('announcement_id', $allIds)
            ->get();

        $readCount = $readIds->count();
        $unread = $total - $readCount;

        $needAck = Announcement::whereIn('id', $allIds)
            ->where('requires_acknowledgment', true)
            ->whereNotIn('id',
                $readIds->whereNotNull('acknowledged_at')->pluck('announcement_id')
            )
            ->count();

        $activePolls = Poll::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>', now());
            })
            ->count();

        return (object) [
            'total'        => $total,
            'unread'       => $unread,
            'needs_ack'    => $needAck,
            'active_polls' => $activePolls,
        ];
    }
}