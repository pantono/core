<?php

namespace Pantono\Core\Factory;

use Pantono\Contracts\Locator\FactoryInterface;
use Twig\Loader\FilesystemLoader;
use Pantono\Utilities\ApplicationHelper;
use Twig\Environment;

class TwigFactory implements FactoryInterface
{
    private string $twigPath;
    private array $twigOptions;

    public function __construct(string $twigPath, array $twigOptions = [])
    {

        $this->twigPath = $twigPath;
        $this->twigOptions = $twigOptions;
    }

    public function createInstance(): Environment
    {
        $loader = new FilesystemLoader([
            ApplicationHelper::getApplicationRoot() . '/' . $this->twigPath,
            ApplicationHelper::getApplicationRoot() . '/vendor/pantono/core/views'
        ]);

        return new Environment($loader, $this->twigOptions);
    }
}
