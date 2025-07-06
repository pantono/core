<?php

namespace Pantono\Core\Helper;

use Pantono\Container\StaticContainer;

class ConfigHelper
{
    public static function getConfigValue(string $name, mixed $default): mixed
    {
        $config = StaticContainer::getContainer()->get('config');
        if (!$config) {
            return $default;
        }
        return $config->get($name, $default);
    }

    public static function getDateFormat(): string
    {
        return self::getConfigValue('date_format', 'd/m/Y');
    }

    public static function getDateTimeFormat(): string
    {
        return self::getConfigValue('date_time_format', 'd/m/Y H:i:s');
    }
}
