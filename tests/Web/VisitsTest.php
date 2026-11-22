<?php

declare(strict_types=1);

namespace App\Tests\Web;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

final class VisitsTest extends KernelTestCase
{
    private const string SCRIPT = '/_vercel/insights/script.js';

    public function testVisitsAreNotCountedOutsideProduction(): void
    {
        self::assertStringNotContainsString(self::SCRIPT, $this->page([]));
    }

    public function testVisitsAreCountedWhenTheAppSaysSo(): void
    {
        self::assertStringContainsString('<script defer src="'.self::SCRIPT.'"></script>', $this->page(['visits_counted' => true]));
    }

    /**
     * @param array<string, mixed> $context
     */
    private function page(array $context): string
    {
        $requests = self::getContainer()->get(RequestStack::class);
        self::assertInstanceOf(RequestStack::class, $requests);
        $requests->push(Request::create('/'));

        $twig = self::getContainer()->get(Environment::class);
        self::assertInstanceOf(Environment::class, $twig);

        return $twig->render('base.html.twig', $context);
    }
}
