<?php

namespace Pantono\Core\Events\Model;

class EventListenerConfig
{
    private string $name;
    private string $class;
    private array $services;
    private array $events;

    public function __construct(string $name, string $class, array $services = [], array $events = [])
    {
        $this->name = $name;
        $this->class = $class;
        $this->services = $services;
        $this->events = $events;
    }

    public static function fromConfig(string $name, array $config): self
    {
        /**
         * @var class-string $className
         */
        $className = $config['class'];
        if (!method_exists($className, 'getSubscribedEvents')) {
            throw new \RuntimeException('Event listener ' . $name . ' does not have the getSubscribedEvents method');
        }
        $method = sprintf('%s::%s', $className, 'getSubscribedEvents');
        if (is_callable($method)) {
            $events = call_user_func($method);
        } else {
            $events = [];
        }

        return new self($name, $config['class'], $config['parameters'] ?? [], array_keys($events));
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function getEvents(): array
    {
        return $this->events;
    }

    public function getSubscribedEvents(): array
    {
        if (!method_exists($this->getClass(), 'getSubscribedEvents')) {
            throw new \RuntimeException('getSubscribedEvents is not set on ' . $this->getClass());
        }
        $className = $this->getClass();

        return $className::getSubscribedEvents();
    }
}
