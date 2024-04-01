<?php

namespace Pantono\Core\Config\Event;

use Symfony\Contracts\EventDispatcher\Event;

class RegisterConfigPathEvent extends Event
{
    private array $paths = [];

    public function addPath(string $path): void
    {
        $this->paths[] = $path;
    }

    public function getPaths(): array
    {
        return $this->paths;
    }
}
