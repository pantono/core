<?php
require_once __DIR__ . '/vendor/autoload.php';
define('APPLICATION_PATH', __DIR__);
define('APPLICATION_ENV', 'test');
$container = new \Pantono\Container\Container();
$container['event_dispatcher'] = new \Symfony\Component\EventDispatcher\EventDispatcher();
$container['hydrator'] = new \Pantono\Hydrator\Hydrator($container);
$container['locator'] = new \Pantono\Container\Service\Locator($container, new \Pantono\Container\Service\Collection\ServiceCollection());
$container['security_context'] = new \Symfony\Component\HttpFoundation\ParameterBag();