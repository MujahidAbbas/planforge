<?php

namespace App\Models;

use App\Enums\PlanRunStepStatus;
use App\Enums\StepType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanRunStep extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'plan_run_id',
        'step',
        'status',
        'attempt',
        'provider',
        'model',
        'prompt_hash',
        'request_meta',
        'rate_limits',
        'error_message',
        'started_at',
        'finished_at',
        'next_attempt_at',
    ];

    protected function casts(): array
    {
        return [
            'step' => StepType::class,
            'status' => PlanRunStepStatus::class,
            'attempt' => 'integer',
            'request_meta' => 'array',
            'rate_limits' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'next_attempt_at' => 'datetime',
        ];
    }

    public function planRun(): BelongsTo
    {
        return $this->belongsTo(PlanRun::class);
    }

    public function documentVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
