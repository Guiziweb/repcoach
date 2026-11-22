<?php

declare(strict_types=1);

namespace App\Tests\Web\Coach\Display;

use App\Tests\ExerciseFixture;
use App\Web\Coach\Display\DisplayToolsReadTheDatabaseAfresh;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\AI\Agent\Toolbox\Event\ToolCallArgumentsResolved;
use Symfony\AI\Platform\Tool\ExecutionReference;
use Symfony\AI\Platform\Tool\Tool;

final class DisplayToolsReadTheDatabaseAfreshTest extends TestCase
{
    public function testItForgetsWhatDoctrineRememberedBeforeATool(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('clear');

        new DisplayToolsReadTheDatabaseAfresh($entityManager)(new ToolCallArgumentsResolved(
            new stdClass(),
            new Tool(new ExecutionReference(self::class), 'display_program', 'A tool.'),
            ['name' => ExerciseFixture::workout('Pecs')->name],
        ));
    }
}
