<?php

declare(strict_types=1);

namespace App\Exercise\Mcp;

use App\Exercise\Enum\BodyPart;
use App\Exercise\Enum\Difficulty;
use App\Exercise\Enum\ExerciseCategory;
use App\Exercise\Enum\Muscle;
use App\Exercise\Exercise;
use App\Exercise\ExerciseRepository;
use Mcp\Capability\Attribute\McpTool;
use Mcp\Capability\Attribute\Schema;
use Mcp\Schema\ToolAnnotations;

#[McpTool(
    name: 'search_exercises',
    title: 'Search exercises',
    description: 'Find exercises in the catalogue. Free text matches names, muscles and body parts. Combine filters to narrow the list down. "matching" is the total number of matches, which may exceed the returned items.',
    annotations: new ToolAnnotations(readOnlyHint: true, idempotentHint: true, openWorldHint: false),
    outputSchema: [
        'type' => 'object',
        'required' => ['items', 'matching'],
        'properties' => [
            'matching' => ['type' => 'integer'],
            'items' => ['type' => 'array', 'items' => ExerciseSummary::SCHEMA],
        ],
    ],
)]
final readonly class SearchExercisesTool
{
    public function __construct(
        private ExerciseRepository $exercises,
    ) {
    }

    /**
     * @return array{matching: int, items: list<ExerciseSummary>}
     */
    public function __invoke(
        #[Schema(description: 'Body part trained, e.g. chest')]
        ?BodyPart $bodyPart = null,
        #[Schema(description: 'Kind of exercise, e.g. strength')]
        ?ExerciseCategory $category = null,
        #[Schema(description: 'Required experience')]
        ?Difficulty $difficulty = null,
        #[Schema(description: 'Muscle the exercise works, as a primary or secondary target')]
        ?Muscle $muscle = null,
        #[Schema(description: 'Free text matched against the name, the muscles worked and the body part, in English. "biceps" and "curl" both work.')]
        ?string $query = null,
        #[Schema(description: 'True only for exercises that need no equipment at all (a pull-up bar, rings or a bench count as equipment), false to exclude them')]
        ?bool $bodyweight = null,
        #[Schema(description: 'How many to return', minimum: 1, maximum: ExerciseRepository::MAX_RESULTS)]
        int $limit = 20,
    ): array {
        $found = $this->exercises->search($bodyPart, $category, $difficulty, $muscle, $query, $bodyweight, $limit);

        return [
            'matching' => $this->exercises->countMatching($bodyPart, $category, $difficulty, $muscle, $query, $bodyweight),
            'items' => array_map(static fn (Exercise $exercise): ExerciseSummary => ExerciseSummary::of($exercise), $found),
        ];
    }
}
