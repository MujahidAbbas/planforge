<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'type' => DocumentType::Prd,
            'current_version_id' => null,
        ];
    }

    public function prd(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DocumentType::Prd,
        ]);
    }

    public function tech(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => DocumentType::Tech,
        ]);
    }
}
