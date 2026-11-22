<?php

declare(strict_types=1);

namespace App\Web\Program;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProgramsController extends AbstractController
{
    public function __construct(
        private readonly ProgramPages $programs,
    ) {
    }

    #[Route('/programs', name: 'programs', methods: ['GET'])]
    public function index(): Response
    {
        $first = $this->programs->first();

        return null === $first
            ? $this->render('programs/empty.html.twig')
            : $this->redirectToRoute('program', ['name' => $first->workout->name]);
    }

    #[Route('/programs/{name<.+>}', name: 'program', methods: ['GET'])]
    public function show(string $name): Response
    {
        $program = $this->programs->named($name);

        return match (true) {
            null === $program => $this->redirectToRoute('programs'),
            $program->workout->name !== $name => $this->redirectToRoute('program', ['name' => $program->workout->name]),
            default => $this->render('programs/show.html.twig', ['program' => $program]),
        };
    }
}
