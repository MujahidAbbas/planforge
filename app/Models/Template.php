<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Enums\TemplateCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection as SupportCollection;

class Template extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'document_type',
        'category',
        'sections',
        'ai_instructions',
        'is_built_in',
        'is_public',
        'is_community',
        'author',
        'usage_count',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'document_type' => DocumentType::class,
            'category' => TemplateCategory::class,
            'is_built_in' => 'boolean',
            'is_public' => 'boolean',
            'is_community' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prdProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'prd_template_id');
    }

    public function techProjects(): HasMany
    {
        return $this->hasMany(Project::class, 'tech_template_id');
    }

    /**
     * Get all available templates for a document type.
     * Includes built-in, community, and user's custom templates.
     */
    public static function getAvailable(DocumentType $type, ?string $userId = null): Collection
    {
        return static::query()
            ->where('document_type', $type)
            ->where(function ($query) use ($userId) {
                $query->where('is_built_in', true)
                    ->orWhere('is_community', true)
                    ->orWhere('is_public', true);

                if ($userId) {
                    $query->orWhere('user_id', $userId);
                }
            })
            ->orderBy('sort_order')
            ->orderByDesc('is_built_in')
            ->orderByDesc('usage_count')
            ->get();
    }

    /**
     * Get templates grouped by category for UI display.
     */
    public static function getGroupedByCategory(DocumentType $type, ?string $userId = null): SupportCollection
    {
        return static::getAvailable($type, $userId)
            ->groupBy(fn (Template $template): string => $template->category?->value ?? 'other')
            ->map(fn (Collection $templates, string $category): array => [
                'category' => TemplateCategory::tryFrom($category),
                'templates' => $templates,
            ]);
    }

    /**
     * Increment usage counter.
     */
    public function recordUsage(): void
    {
        $this->increment('usage_count');
    }

    /**
     * Get formatted sections for prompt injection.
     * Includes detailed guidance for each section.
     */
    public function getFormattedSectionsForPrompt(): string
    {
        if (empty($this->sections)) {
            return '';
        }

        $formatted = [];

        foreach ($this->sections as $index => $section) {
            $required = ($section['required'] ?? false) ? '(Required)' : '(Optional)';

            $sectionText = sprintf(
                "%d. **%s** %s\n   %s",
                $index + 1,
                $section['title'],
                $required,
                $section['description'] ?? ''
            );

            // Add detailed guidance if available
            if (! empty($section['guidance'])) {
                $sectionText .= sprintf("\n   *Guidance: %s*", $section['guidance']);
            }

            $formatted[] = $sectionText;
        }

        return implode("\n\n", $formatted);
    }
}
