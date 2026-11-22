<?php

declare(strict_types=1);

namespace App\Tests\Web\Coach;

use Symfony\AI\Platform\Model;
use Symfony\AI\Platform\Platform;
use Symfony\AI\Platform\Result\ResultInterface;
use Symfony\AI\Platform\Test\MockPlatformFactory;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Throwable;

final class ScriptedCoach
{
    public const string DEFAULT_ANSWER = 'The coach answers.';

    /**
     * @var list<ResultInterface|string|Throwable>
     */
    private array $answers = [];

    private string $lastHeard = '';

    public function answers(ResultInterface|string|Throwable ...$answers): void
    {
        array_push($this->answers, ...array_values($answers));
    }

    public function lastHeard(): string
    {
        return $this->lastHeard;
    }

    public function platform(EventDispatcherInterface $dispatcher): Platform
    {
        return MockPlatformFactory::createPlatform(eventDispatcher: $dispatcher, responses: function (Model $model, array|string|object $input): ResultInterface|string {
            $this->lastHeard = json_encode($input, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_UNICODE);
            $answer = array_shift($this->answers) ?? self::DEFAULT_ANSWER;

            return $answer instanceof Throwable ? throw $answer : $answer;
        });
    }
}
