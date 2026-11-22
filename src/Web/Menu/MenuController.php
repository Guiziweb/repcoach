<?php

declare(strict_types=1);

namespace App\Web\Menu;

use App\Session\WorkoutSessionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class MenuController extends AbstractController
{
    public function __construct(
        private readonly WorkoutSessionRepository $sessions,
    ) {
    }

    #[Route('/', name: 'menu', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('menu.html.twig', ['live' => $this->sessions->open()]);
    }
}
