<?php

namespace Pantono\Core\Events;

use Symfony\Component\EventDispatcher\EventDispatcher;

class EventHelper
{
    private EventDispatcher $dispatcher;

    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    public function fireEvent(string $eventClass, mixed $current, mixed $previous = null): void
    {
        $event = new $eventClass;
        if ($previous && method_exists($event, 'setPrevious')) {
            $event->setPrevious($previous);
        }
        if (method_exists($event, 'setCurrent')) {
            $event->setCurrent($current);
        }
        $this->dispatcher->dispatch($event);
    }
}
