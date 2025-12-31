<?php

namespace App\Models;

use App\Enums\ProjectStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'name',
        'idea',
        'constraints',
        'preferred_provider',
        'preferred_model',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'constraints' => 'array',
            'status' => ProjectStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function planRuns(): HasMany
    {
        return $this->hasMany(PlanRun::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function epics(): HasMany
    {
        return $this->hasMany(Epic::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function taskSets(): HasMany
    {
        return $this->hasMany(TaskSet::class);
    }

    public function latestTaskSet(): ?TaskSet
    {
        return $this->taskSets()->latest()->first();
    }
}
