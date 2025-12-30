<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Story extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'epic_id',
        'title',
        'description',
        'acceptance_criteria',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'acceptance_criteria' => 'array',
            'sort_order' => 'integer',
        ];
    }

    public function epic(): BelongsTo
    {
        return $this->belongsTo(Epic::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
