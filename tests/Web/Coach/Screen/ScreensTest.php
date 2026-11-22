<?php

declare(strict_types=1);

namespace App\Tests\Web\Coach\Screen;

use App\Tests\ExerciseFixture;
use App\Tests\ResetsTheDatabase;
use App\Web\Coach\Screen\Screens;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ScreensTest extends KernelTestCase
{
    use ResetsTheDatabase;

    private Screens $screens;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = $this->emptyDatabase();

        $exercise = ExerciseFixture::exercise();
        $entityManager->persist($exercise);
        $entityManager->persist(ExerciseFixture::workout('Pecs', $exercise));
        $entityManager->flush();

        $screens = self::getContainer()->get(Screens::class);
        self::assertInstanceOf(Screens::class, $screens);
        $this->screens = $screens;
    }

    #[DataProvider('pages')]
    public function testEachPageIsDescribedByItsScreen(string $url, string $description): void
    {
        self::assertStringContainsString($description, $this->screens->describe($url));
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function pages(): iterable
    {
        yield 'menu' => ['/', 'The menu'];
        yield 'program' => ['/programs/Pecs', 'The workout "Pecs"'];
        yield 'unknown program' => ['/programs/Legs', 'no workout on it'];
        yield 'sessions' => ['/sessions?page=1', 'The list of finished sessions, empty'];
        yield 'session' => ['/sessions/7', 'A session that no longer exists'];
        yield 'live session' => ['/session', 'No session is open'];
    }

    #[DataProvider('blindSpots')]
    public function testAPageWithoutAScreenIsNotDescribed(string $url): void
    {
        self::assertSame('', $this->screens->describe($url));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function blindSpots(): iterable
    {
        yield 'unknown page' => ['/nowhere'];
        yield 'action, not a page' => ['/coach'];
    }
}
