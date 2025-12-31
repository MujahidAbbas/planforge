<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\PlanRunStepStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaskSet extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'project_id',
        'source_tech_version_id',
        'source_prd_version_id',
        'plan_run_id',
        'plan_run_step_id',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function sourceTechVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'source_tech_version_id');
    }

    public function sourcePrdVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'source_prd_version_id');
    }

    public function planRun(): BelongsTo
    {
        return $this->belongsTo(PlanRun::class);
    }

    public function planRunStep(): BelongsTo
    {
        return $this->belongsTo(PlanRunStep::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get status from the associated plan run step.
     */
    public function getStatusAttribute(): ?PlanRunStepStatus
    {
        return $this->planRunStep?->status;
    }

    /**
     * Check if tasks are stale (tech spec has been updated since generation).
     */
    public function isStale(): bool
    {
        $latestTechVersion = $this->project->documents()
            ->where('type', DocumentType::Tech)
            ->first()
            ?->currentVersion;

        return $latestTechVersion
            && $this->source_tech_version_id !== $latestTechVersion->id;
    }
}
