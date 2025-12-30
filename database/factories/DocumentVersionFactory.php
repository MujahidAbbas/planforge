<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentVersion>
 */
class DocumentVersionFactory extends Factory
{
    protected $model = DocumentVersion::class;

    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'plan_run_id' => null,
            'plan_run_step_id' => null,
            'created_by' => null,
            'content_md' => fake()->paragraphs(5, true),
            'content_json' => null,
            'summary' => fake()->sentence(),
        ];
    }

    public function withContent(string $content): static
    {
        return $this->state(fn (array $attributes) => [
            'content_md' => $content,
        ]);
    }
}
