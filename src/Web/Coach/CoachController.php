<?php

declare(strict_types=1);

namespace App\Web\Coach;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\RateLimit;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\UX\Turbo\TurboBundle;

#[Route('/coach', name: 'coach_', methods: ['POST'], format: TurboBundle::STREAM_FORMAT)]
#[IsCsrfTokenValid('coach')]
final class CoachController extends AbstractController
{
    public function __construct(
        private readonly Coach $coach,
    ) {
    }

    #[Route('', name: 'reply')]
    #[RateLimit('coach_client')]
    #[RateLimit('coach_total', key: 'everyone')]
    public function reply(#[MapRequestPayload] CoachRequest $ask): Response
    {
        return $this->render('coach/reply.stream.html.twig', [
            'reply' => $this->coach->reply(trim($ask->message), $ask->screen),
        ]);
    }

    #[Route('/confirm', name: 'confirm')]
    #[RateLimit('coach_client')]
    #[RateLimit('coach_total', key: 'everyone')]
    public function confirm(#[MapRequestPayload] ConfirmRequest $confirmation): Response
    {
        return $this->render('coach/reply.stream.html.twig', [
            'reply' => $this->coach->confirm($confirmation->id, $confirmation->screen),
        ]);
    }

    #[Route('/forget', name: 'forget')]
    public function forget(): Response
    {
        $this->coach->forget();

        return $this->render('coach/forget.stream.html.twig');
    }
}
