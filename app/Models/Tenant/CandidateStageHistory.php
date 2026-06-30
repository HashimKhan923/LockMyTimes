<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CandidateStageHistory extends TenantModel
{
    protected $table = 'candidate_stage_history';

    protected $fillable = [
        'candidate_id', 'from_stage', 'to_stage',
        'changed_by', 'reason',
    ];

    public function candidate(): BelongsTo  { return $this->belongsTo(Candidate::class); }
    public function changer(): BelongsTo    { return $this->belongsTo(User::class, 'changed_by'); }
}