<?php

namespace Pantono\Core\Application\Extensions\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\Environment;

final class TwigDumpExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        // dump is safe if var_dump is overridden by xdebug
        $isDumpOutputHtmlSafe = \extension_loaded('xdebug')
            // false means that it was not set (and the default is on) or it explicitly enabled
            && (false === \ini_get('xdebug.overload_var_dump') || \ini_get('xdebug.overload_var_dump'))
            // false means that it was not set (and the default is on) or it explicitly enabled
            // xdebug.overload_var_dump produces HTML only when html_errors is also enabled
            && (false === \ini_get('html_errors') || \ini_get('html_errors'))
            || 'cli' === \PHP_SAPI;

        return [
            new TwigFunction('dump', [self::class, 'dump'], ['is_safe' => $isDumpOutputHtmlSafe ? ['html'] : [], 'needs_context' => true, 'needs_environment' => true, 'is_variadic' => true]),
        ];
    }

    /**
     * @internal
     */
    public static function dump(Environment $env, $context, ...$vars): string
    {
        if (!$env->isDebug()) {
            return '';
        }
        dump($vars);

        return ob_get_clean();
    }
}
