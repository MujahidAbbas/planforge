<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Epic extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'project_id',
        'plan_run_id',
        'title',
        'summary',
        'priority',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'priority' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function planRun(): BelongsTo
    {
        return $this->belongsTo(PlanRun::class);
    }

    public function stories(): HasMany
    {
        return $this->hasMany(Story::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
