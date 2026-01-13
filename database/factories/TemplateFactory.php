<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Enums\TemplateCategory;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Template>
 */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'document_type' => fake()->randomElement(DocumentType::cases()),
            'category' => fake()->randomElement(TemplateCategory::cases()),
            'sections' => [
                ['title' => 'Overview', 'description' => 'Brief summary', 'required' => true],
                ['title' => 'Details', 'description' => 'More information', 'required' => false],
            ],
            'ai_instructions' => null,
            'is_built_in' => false,
            'is_public' => false,
            'is_community' => false,
            'author' => null,
            'usage_count' => 0,
            'sort_order' => 0,
        ];
    }

    public function builtIn(): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'is_built_in' => true,
        ]);
    }

    public function community(string $author): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => null,
            'is_community' => true,
            'author' => $author,
        ]);
    }

    public function prd(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => DocumentType::Prd,
        ]);
    }

    public function tech(): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => DocumentType::Tech,
        ]);
    }

    public function inCategory(TemplateCategory $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }
}
