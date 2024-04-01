<?php

namespace Pantono\Core\Cache\Helper;

class CacheHelper
{
    public static function cleanCacheKey(string $key): string
    {
        $key = str_replace('/', '-', $key);
        $key = str_replace('\\', '--', $key);
        $key = str_replace('{', '', $key);
        $key = str_replace('}', '', $key);
        $key = str_replace('@', '-at-', $key);
        return $key;
    }
}