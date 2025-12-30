<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'document_id',
        'plan_run_id',
        'plan_run_step_id',
        'created_by',
        'content_md',
        'content_json',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'content_json' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function planRun(): BelongsTo
    {
        return $this->belongsTo(PlanRun::class);
    }

    public function planRunStep(): BelongsTo
    {
        return $this->belongsTo(PlanRunStep::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
