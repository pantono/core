<?php

namespace Pantono\Core\Application\Traits;

use Twig\Environment;
use Pantono\Hydrator\Locator\StaticLocator;

trait TwigRendererTrait
{
    public function render(string $template, array $variables = []): string
    {
        /**
         * @var Environment $twig
         */
        $twig = StaticLocator::getLocator()->loadDependency('@Twig');
        return $twig->render($template, $variables);
    }
}
