<?php

declare(strict_types=1);

namespace App\Web\Session;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class SessionsController extends AbstractController
{
    public function __construct(
        private readonly SessionPages $pages,
    ) {
    }

    #[Route('/sessions', name: 'sessions', methods: ['GET'])]
    public function index(#[MapQueryParameter] int $page = 1): Response
    {
        return $this->render('sessions/index.html.twig', ['list' => $this->pages->page($page)]);
    }

    #[Route('/session', name: 'live_session', methods: ['GET'])]
    public function live(): Response
    {
        return $this->render('sessions/live.html.twig', ['view' => $this->pages->live()]);
    }

    #[Route('/sessions/{id<\d+>}', name: 'session', methods: ['GET'])]
    public function show(int $id): Response
    {
        $view = $this->pages->session($id);

        return match (true) {
            null !== $view => $this->render('sessions/show.html.twig', ['view' => $view]),
            $this->pages->isLive($id) => $this->redirectToRoute('live_session'),
            default => $this->redirectToRoute('sessions'),
        };
    }
}
