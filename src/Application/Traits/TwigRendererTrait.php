<?php

namespace Pantono\Core\Application\Traits;

use Pantono\Container\StaticContainer;
use Twig\Environment;

trait TwigRendererTrait
{
    public function render(string $template, array $variables = [])
    {
        /**
         * @var Environment $twig
         */
        $twig = StaticContainer::getLocator()->loadDependency('@Twig');
        return $twig->render($template, $variables);
    }
}
