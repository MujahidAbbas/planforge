<?php

namespace App\Models;

use App\Enums\IntegrationProvider;
use App\Enums\IntegrationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Integration extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'project_id',
        'provider',
        'status',
        'credentials',
        'settings',
        'error_message',
        'last_synced_at',
    ];

    protected $hidden = ['credentials'];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
            'credentials' => 'encrypted:array',
            'last_synced_at' => 'datetime',
            'provider' => IntegrationProvider::class,
            'status' => IntegrationStatus::class,
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function externalLinks(): HasMany
    {
        return $this->hasMany(ExternalLink::class);
    }

    public function syncRuns(): HasMany
    {
        return $this->hasMany(SyncRun::class);
    }

    public function isConnected(): bool
    {
        return $this->status === IntegrationStatus::Connected;
    }

    public function getInstallationId(): ?string
    {
        return $this->settings['installation_id'] ?? null;
    }

    public function getRepoFullName(): ?string
    {
        $owner = $this->settings['owner'] ?? null;
        $repo = $this->settings['repo'] ?? null;

        return $owner && $repo ? "{$owner}/{$repo}" : null;
    }
}
