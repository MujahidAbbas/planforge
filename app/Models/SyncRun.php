<?php

namespace App\Models;

use App\Enums\SyncRunStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SyncRun extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'integration_id',
        'user_id',
        'direction',
        'trigger',
        'status',
        'total_count',
        'created_count',
        'updated_count',
        'skipped_count',
        'failed_count',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_count' => 'integer',
            'created_count' => 'integer',
            'updated_count' => 'integer',
            'skipped_count' => 'integer',
            'failed_count' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'status' => SyncRunStatus::class,
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function incrementStat(string $type): void
    {
        $column = "{$type}_count";
        if (in_array($column, ['created_count', 'updated_count', 'skipped_count', 'failed_count'])) {
            $this->increment($column);
        }
    }
}
