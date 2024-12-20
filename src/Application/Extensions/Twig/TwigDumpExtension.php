<?php

namespace Pantono\Core\Application\Extensions\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Environment;

final class TwigDumpExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('dump', [self::class, 'dump'], ['is_safe' => ['html'], 'needs_context' => true, 'needs_environment' => true, 'is_variadic' => true]),
        ];
    }

    public static function dump(Environment $env, array $arg = [], mixed ...$vars): string
    {
        if (!$env->isDebug()) {
            return '';
        }
        ob_start();
        dump($vars[0]);
        $str = ob_get_clean();
        return $str ?: '';
    }
}
