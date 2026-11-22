<?php

declare(strict_types=1);

namespace App\Web\Coach\Screen;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

final readonly class Screens
{
    /**
     * @param ServiceProviderInterface<DescribesScreen> $descriptions
     */
    public function __construct(
        #[Autowire(service: 'router')]
        private RequestMatcherInterface $router,
        #[AutowireLocator(DescribesScreen::class)]
        private ServiceProviderInterface $descriptions,
    ) {
    }

    public function describe(string $url): string
    {
        $page = Request::create($url);

        try {
            $attributes = $this->router->matchRequest($page);
        } catch (ExceptionInterface) {
            return '';
        }

        $route = $attributes['_route'] ?? null;

        if (!\is_string($route) || !$this->descriptions->has($route)) {
            return '';
        }

        return $this->descriptions->get($route)->describe([...$page->query->all(), ...$attributes]);
    }
}
