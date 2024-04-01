<?php

namespace Pantono\Core\Cache\Factory;

use Pantono\Contracts\Locator\FactoryInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Pantono\Utilities\ApplicationHelper;

class FilesystemCacheFactory implements FactoryInterface
{
    private string $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function createInstance(): FilesystemAdapter
    {
        $path = realpath(ApplicationHelper::getApplicationRoot() . '/' . $this->path);
        return new FilesystemAdapter('', 3600, $path);
    }
}
