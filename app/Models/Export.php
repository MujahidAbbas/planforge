<?php

namespace App\Models;

use App\Enums\ExportStatus;
use App\Enums\ExportType;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Export extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'project_id',
        'user_id',
        'type',
        'status',
        'disk',
        'path',
        'filename',
        'size_bytes',
        'expires_at',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'type' => ExportType::class,
            'status' => ExportStatus::class,
            'size_bytes' => 'integer',
            'expires_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
