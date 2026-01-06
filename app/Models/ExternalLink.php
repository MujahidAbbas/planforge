<?php

namespace App\Models;

use App\Enums\ExternalLinkSyncStatus;
use App\Enums\IntegrationProvider;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExternalLink extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'integration_id',
        'task_id',
        'provider',
        'external_id',
        'external_number',
        'external_url',
        'external_state',
        'sync_status',
        'sync_error',
        'last_synced_at',
        'last_synced_hash',
    ];

    protected function casts(): array
    {
        return [
            'external_number' => 'integer',
            'last_synced_at' => 'datetime',
            'provider' => IntegrationProvider::class,
            'sync_status' => ExternalLinkSyncStatus::class,
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
