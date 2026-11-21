<?php

declare(strict_types=1);

namespace App\Tests\Shared\Mcp;

use App\Exercise\ExerciseRepository;
use App\Shared\Mcp\ReadsTheDatabaseAfresh;
use App\Tests\ExerciseFixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Mcp\Event\RequestEvent;
use Mcp\Schema\Request\PingRequest;
use Mcp\Server\Session\InMemorySessionStore;
use Mcp\Server\Session\Session;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[CoversClass(ReadsTheDatabaseAfresh::class)]
final class ReadsTheDatabaseAfreshTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;

    private ExerciseRepository $exercises;

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $this->entityManager = $entityManager;

        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schema = new SchemaTool($entityManager);
        $schema->dropSchema($metadata);
        $schema->createSchema($metadata);

        $entityManager->persist(ExerciseFixture::exercise());
        $entityManager->flush();

        $exercises = self::getContainer()->get(ExerciseRepository::class);
        self::assertInstanceOf(ExerciseRepository::class, $exercises);
        $this->exercises = $exercises;
    }

    public function testAWriteMadeByAnotherProcessIsSeenOnTheNextRequest(): void
    {
        self::assertSame('Barbell Bench Press', $this->exercises->get('bench-press')->name);

        $this->entityManager->getConnection()->executeStatement("UPDATE exercise SET name = 'Renamed'");
        $this->receiveARequest();

        self::assertSame('Renamed', $this->exercises->get('bench-press')->name);
    }

    public function testWithoutANewRequestTheLoadedStateIsKept(): void
    {
        $before = $this->exercises->get('bench-press');

        $this->entityManager->getConnection()->executeStatement("UPDATE exercise SET name = 'Renamed'");

        self::assertSame($before, $this->exercises->get('bench-press'));
    }

    private function receiveARequest(): void
    {
        $dispatcher = self::getContainer()->get('event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);

        $dispatcher->dispatch(new RequestEvent(new PingRequest(), new Session(new InMemorySessionStore())));
    }

    protected function tearDown(): void
    {
        $this->entityManager->clear();
        parent::tearDown();
    }
}
