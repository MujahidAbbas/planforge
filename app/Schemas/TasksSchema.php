<?php

namespace App\Schemas;

use Prism\Prism\Schema\ArraySchema;
use Prism\Prism\Schema\EnumSchema;
use Prism\Prism\Schema\ObjectSchema;
use Prism\Prism\Schema\StringSchema;

final class TasksSchema
{
    public static function make(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'task_generation_result',
            description: 'Generated tasks from tech spec',
            properties: [
                new ArraySchema(
                    name: 'tasks',
                    description: 'List of implementation-ready tasks',
                    items: self::taskSchema()
                ),
            ],
            requiredFields: ['tasks']
        );
    }

    private static function taskSchema(): ObjectSchema
    {
        return new ObjectSchema(
            name: 'task',
            description: 'A single implementation task',
            properties: [
                new StringSchema('temp_id', 'Temporary ID for dependency references'),
                new StringSchema('title', 'Short, actionable task title'),
                new StringSchema('description', 'Implementation notes and context'),
                new EnumSchema(
                    name: 'category',
                    description: 'Task category',
                    options: ['backend', 'frontend', 'db', 'infra', 'tests', 'docs']
                ),
                new EnumSchema(
                    name: 'status',
                    description: 'Initial task status',
                    options: ['todo', 'doing', 'done']
                ),
                new EnumSchema(
                    name: 'priority',
                    description: 'Task priority',
                    options: ['low', 'med', 'high']
                ),
                new StringSchema('estimate', 'Rough estimate (e.g., 2h, 1d, 3sp)', nullable: true),
                new ArraySchema(
                    name: 'acceptance_criteria',
                    description: 'Testable acceptance criteria',
                    items: new StringSchema('criterion', 'A single criterion')
                ),
                new ArraySchema(
                    name: 'source_refs',
                    description: 'References to Tech Spec sections',
                    items: new StringSchema('ref', 'Section reference')
                ),
                new ArraySchema(
                    name: 'depends_on',
                    description: 'temp_ids of tasks this depends on',
                    items: new StringSchema('dep', 'Dependency temp_id')
                ),
                new ArraySchema(
                    name: 'labels',
                    description: 'Labels for filtering',
                    items: new StringSchema('label', 'A label')
                ),
            ],
            requiredFields: [
                'temp_id',
                'title',
                'description',
                'category',
                'status',
                'priority',
                'estimate',
                'acceptance_criteria',
                'source_refs',
                'depends_on',
                'labels',
            ]
        );
    }
}
