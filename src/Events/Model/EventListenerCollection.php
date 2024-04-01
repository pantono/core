<?php

namespace Pantono\Core\Events\Model;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Pantono\Contracts\Locator\LocatorInterface;
use Pantono\Core\Events\EventSubscriberProxy;

class EventListenerCollection
{
    /**
     * @var EventListenerConfig[]
     */
    private array $listeners = [];
    private LocatorInterface $locator;
    private EventDispatcher $eventDispatcher;

    public function __construct(LocatorInterface $locator, EventDispatcher $eventDispatcher)
    {
        $this->locator = $locator;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function addEventListener(string $name, array $config): void
    {
        $this->listeners[] = EventListenerConfig::fromConfig($name, $config);
    }

    public function registerSubscribers(): void
    {
        foreach ($this->listeners as $listener) {
            /**
             * This may look strange, but event dispatcher subscribe is called within the constructor of
             * the proxy. It only needs the class to be instantiated
             **/
            new EventSubscriberProxy($listener, $this->locator, $this->eventDispatcher);
        }
    }
}
