<?php

namespace Pantono\Core\Events;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Pantono\Core\Events\Model\EventListenerConfig;
use Pantono\Contracts\Locator\LocatorInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;

class EventSubscriberProxy implements EventSubscriberInterface
{
    private EventListenerConfig $config;
    private static array $events = [];
    private LocatorInterface $locator;

    public function __construct(EventListenerConfig $config, LocatorInterface $locator, EventDispatcher $dispatcher)
    {
        $this->config = $config;
        $this->locator = $locator;
        self::$events = $config->getSubscribedEvents();
        $dispatcher->addSubscriber($this);
    }

    public static function getSubscribedEvents(): array
    {
        /**
         * This will be overwritten every time this class gets instantiated, however as the subscriber calls
         * addSubscriber on construct it should be ok.
         */
        return self::$events;
    }

    public function __call(string $name, array $arguments): mixed
    {
        if (empty($this->config->getServices())) {
            $class = $this->locator->getClassAutoWire($this->config->getClass());
        } else {
            $class = $this->locator->loadClass($this->config->getClass(), $this->config->getServices());
        }
        $callable = [$class, $name];
        if (is_callable($callable) === false) {
            throw new \RuntimeException('Method ' . $name . ' does not exist on ' . $this->config->getClass());
        }
        return call_user_func($callable, ...$arguments);
    }
}
