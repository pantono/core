<?php

namespace Pantono\Core\Helper;

use Pantono\Container\StaticContainer;
use Pantono\Config\Config;

class ConfigHelper
{
    public static function getConfigValue(string $name, mixed $default): mixed
    {
        /**
         * @var ?Config $config
         */
        $config = StaticContainer::getContainer()->get('config');
        if (!$config) {
            return $default;
        }
        return $config->getApplicationConfig()->getValue($name, $default);
    }

    public static function getApplicationConfig(): ?Config
    {
        return StaticContainer::getContainer()->get('config');
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
