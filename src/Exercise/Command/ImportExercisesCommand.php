<?php

declare(strict_types=1);

namespace App\Exercise\Command;

use App\Exercise\Exercise;
use App\Exercise\Import\DatasetUnavailable;
use App\Exercise\Import\ExerciseFactory;
use App\Exercise\Import\InvalidExerciseRow;
use App\Exercise\Import\RepDbDataset;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:import-exercises', description: 'Load or refresh the exercise catalogue from the RepDB dataset')]
final class ImportExercisesCommand
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly RepDbDataset $dataset,
        private readonly ExerciseFactory $exercises,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        try {
            $imported = $this->entityManager->wrapInTransaction(fn (): int => $this->refreshCatalogue());
        } catch (DatasetUnavailable|InvalidExerciseRow $failure) {
            $io->error($failure->getMessage());

            return Command::FAILURE;
        }

        $io->success(\sprintf('%d exercises read, %d now in the catalogue.', $imported, $this->entityManager->getRepository(Exercise::class)->count()));

        return Command::SUCCESS;
    }

    private function refreshCatalogue(): int
    {
        $known = $this->entityManager->getRepository(Exercise::class)->findAll();
        $byId = [];
        foreach ($known as $exercise) {
            $byId[$exercise->id] = $exercise;
        }

        $seen = 0;

        foreach ($this->dataset->rows() as $row) {
            $fresh = $this->exercises->fromRepDb($row);

            if (isset($byId[$fresh->id])) {
                $byId[$fresh->id]->refreshFrom($fresh);
            } else {
                $this->entityManager->persist($fresh);
            }

            ++$seen;
        }

        $this->entityManager->flush();

        return $seen;
    }
}
