<?php

namespace Pantono\Core\Application\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Pantono\Container\Container;

class PostBootstrapEvent extends Event
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getContainer(): Container
    {
        return $this->container;
    }
}
