<?php

namespace App\Models;

use App\Enums\PlanRunStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanRun extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'project_id',
        'triggered_by',
        'status',
        'provider',
        'model',
        'input_snapshot',
        'metrics',
        'error_message',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PlanRunStatus::class,
            'input_snapshot' => 'array',
            'metrics' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(PlanRunStep::class);
    }

    public function documentVersions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class);
    }

    public function epics(): HasMany
    {
        return $this->hasMany(Epic::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
