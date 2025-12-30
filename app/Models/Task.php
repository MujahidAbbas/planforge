<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'project_id',
        'epic_id',
        'story_id',
        'plan_run_id',
        'plan_run_step_id',
        'title',
        'description',
        'acceptance_criteria',
        'estimate',
        'labels',
        'depends_on',
        'status',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'acceptance_criteria' => 'array',
            'labels' => 'array',
            'depends_on' => 'array',
            'status' => TaskStatus::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class);
    }

    public function story(): BelongsTo
    {
        return $this->belongsTo(Story::class);
    }

    public function planRun(): BelongsTo
    {
        return $this->belongsTo(PlanRun::class);
    }

    public function planRunStep(): BelongsTo
    {
        return $this->belongsTo(PlanRunStep::class);
    }
}
