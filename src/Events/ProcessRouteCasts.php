<?php

namespace Pantono\Core\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pantono\Core\Router\Event\PreRequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Pantono\Utilities\DateTimeParser;
use ReflectionClass;
use Pantono\Contracts\Locator\LocatorInterface;

class ProcessRouteCasts implements EventSubscriberInterface
{
    private LocatorInterface $locator;

    public function __construct(LocatorInterface $locator)
    {
        $this->locator = $locator;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PreRequestEvent::class => [
                ['processCasts', 255]
            ]
        ];
    }

    public function processCasts(PreRequestEvent $event): void
    {
        $endpoint = $event->getEndpoint();
        $request = $event->getRequest();
        foreach ($endpoint->getRouteCasts() as $field => $cast) {
            $routeValue = $request->query->all()[$field] ?? null;
            $castResult = is_scalar($routeValue) ? $this->processCast($cast, $routeValue) : null;
            if ($castResult === null) {
                throw new NotFoundHttpException('404 Not Found');
            }
            // InputBag (query/request) cannot hold objects, so the cast value is exposed via attributes & processed parameters
            $request->attributes->set($field, $castResult);
        }
    }

    private function processCast(string $cast, mixed $value): mixed
    {
        if (!class_exists($cast)) {
            throw new \Exception('Route cast class ' . $cast . ' does not exist');
        }
        return $this->locator->lookupRecord($cast, $value);
    }
}
