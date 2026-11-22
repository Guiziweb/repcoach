<?php

declare(strict_types=1);

namespace App\Web\Language;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class LanguageController extends AbstractController
{
    #[Route('/language/{language}', name: 'language', requirements: ['language' => 'en|fr'], methods: ['GET'])]
    public function choose(string $language, Request $request): RedirectResponse
    {
        $request->getSession()->set(RemembersTheLanguage::CHOSEN, $language);

        return $this->redirectToRoute('menu');
    }
}
