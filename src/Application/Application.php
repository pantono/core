<?php

namespace Pantono\Core\Application;

use Pantono\Container\Container;
use Pantono\Container\Service\Collection\ServiceCollection;
use Pantono\Container\Service\Model\Service;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pantono\Core\Application\Event\PreBootstrapEvent;
use Pantono\Core\Application\Event\PostBootstrapEvent;
use Pantono\Container\Service\Locator;
use Pantono\Database\Connection\ConnectionCollection;
use Pantono\Database\Adapter\MysqlDb;
use Pantono\Core\Validator\Collection\ValidatorCollection;
use Pantono\Core\Validator\Collection\ValidatorConfig;
use Pantono\Core\Validator\RequestValidator;
use Pantono\Container\StaticContainer;
use Pantono\Hydrator\Hydrator;
use League\Fractal\Manager;
use Pantono\Core\Security\Collection\SecurityGateCollection;
use Pantono\Core\Security\Model\SecurityGate;
use Pantono\Core\Security\Context\SecurityContext;
use Pantono\Config\Config;
use Pantono\Core\Security\RequestSecurityValidator;
use Pantono\Config\Helper\RegisterConfigPath;
use Psr\EventDispatcher\EventDispatcherInterface;
use Pantono\Core\Events\Model\EventListenerCollection;
use Pantono\Core\Router\Router;
use Pantono\Core\Router\Model\EndpointCollection;
use Pantono\Core\Router\Model\EndpointDefinition;
use Pantono\Core\CommandLine\Model\CommandCollection;
use Pantono\Core\CommandLine\Model\CommandConfig;
use Pantono\Utilities\ApplicationHelper;
use Pantono\Hydrator\Locator\StaticLocator;
use Pantono\Contracts\Locator\LocatorInterface;
use Pantono\Cache\Factory\FilesystemCacheFactory;

abstract class Application
{
    protected string $basePath;
    protected Container $container;
    protected string $env;

    public function __construct(string $env, string $basePath, ?EventDispatcher $dispatcher = null, ?Container $container = null)
    {
        define('APPLICATION_ENV', $env);
        if (!defined('APPLICATION_PATH')) {
            define('APPLICATION_PATH', $basePath);
        }
        if (str_ends_with($basePath, '/') === false) {
            $basePath .= '/';
        }
        if (file_exists($basePath . '/conf') && is_dir($basePath . '/conf')) {
            RegisterConfigPath::registerPath($basePath . '/conf');
        }
        $modulesInstalled = glob($basePath . '/vendor/pantono/*');
        if ($modulesInstalled !== false) {
            foreach ($modulesInstalled as $module) {
                if (file_exists($module . '/conf') && is_dir($module . '/conf')) {
                    $parts = explode('/', $module);
                    RegisterConfigPath::registerModule(array_pop($parts));
                }
            }
        }
        $this->basePath = $basePath;
        $this->env = $env;
        if ($container === null) {
            $container = new Container();
        }
        $this->container = $container;
        StaticContainer::setContainer($container);
        $releaseTimestampFile = ApplicationHelper::getApplicationRoot() . '/release_timestamp';
        if (file_exists($releaseTimestampFile)) {
            $data = file_get_contents($releaseTimestampFile);
            if ($data) {
                $releaseTimestamp = trim($data);
                define('RELEASE_TIME', $releaseTimestamp);
            }
        }
        if ($dispatcher === null) {
            $dispatcher = new EventDispatcher();
        }
        $collection = new ServiceCollection();
        if (!$this->container->hasService('ServiceCollection')) {
            $this->container->addService('ServiceCollection', $collection);
        }
        if (!$this->container->hasService('Hydrator')) {
            $this->container->addService('Hydrator', new Hydrator($container));
        }
        $this->container->addService('EventDispatcher', $dispatcher, [EventDispatcherInterface::class]);
        if (!$this->container->hasService('ServiceLocator')) {
            $this->container->addService('ServiceLocator', new Locator($this->container, $collection), [LocatorInterface::class]);
        }
        StaticLocator::setLocator($this->container->getService('ServiceLocator'));
    }

    abstract public function run(): int;

    public function bootstrap(): void
    {
        $this->container->getEventDispatcher()->dispatch(new PreBootstrapEvent());
        $this->registerShutdownFunc();
        $this->loadCache();
        $this->loadConfig();
        $this->setTimezone();
        $this->initDatabase();
        $this->loadServices();
        $this->container->addService('SecurityContext', new SecurityContext());
        $this->loadEventListeners();
        $this->loadValidators();
        $this->loadSecurityGates();
        $this->loadEndpoints();
        $this->loadFractal();
        if (php_sapi_name() === 'cli') {
            $this->loadCommands();
        }
        $this->container->getEventDispatcher()->dispatch(new PostBootstrapEvent());
    }

    private function loadCache(): void
    {
        $service = new FilesystemCacheFactory(ApplicationHelper::getApplicationRoot() . '/cache');
        $this->container->addService('SystemCache', $service);
    }

    private function loadConfig(): void
    {
        $this->container->getEventDispatcher()->addSubscriber(new RegisterConfigPath());
        $config = new Config($this->container->getEventDispatcher(), $this->container->getService('SystemCache'));
        $this->container['config'] = $config;
        $this->container->addService('Config', $config);
    }

    private function setTimezone(): void
    {
        $timezone = $this->container['config']->getConfigForType('config')->getValue('application.timezone') ?? 'UTC';
        date_default_timezone_set($timezone);
    }

    private function loadServices(): void
    {
        $collection = $this->container->getServiceCollection();
        $collection->addService(new Service('ServiceCollection', ServiceCollection::class, []));
        foreach ($this->container->getConfig()->getConfigForType('services')->getAllData() as $name => $config) {
            $service = new Service($name, $config['class'], $config['parameters'] ?? [], $config['aliases'] ?? []);
            $collection->addService($service);
        }
    }

    private function initDatabase(): void
    {
        $config = $this->container->getConfig();
        $collection = new ConnectionCollection();
        $appConfig = $config->getConfigForType('config');
        foreach ($appConfig->getValue('resources.database', []) as $name => $database) {
            if ($database['type'] === 'mysql') {
                $db = new MysqlDb($database['dsn'], $database['user'], $database['password'], $database['options'] ?? null);
            } elseif ($database['type'] === 'mssql') {
                $db = new MysqlDb($database['dsn'], $database['user'], $database['password'], $database['options'] ?? null);
            } else {
                throw new \RuntimeException('Database type ' . $database['type'] . ' not registered');
            }

            $collection->addConnection($name, $database['type'], $db);
        }
        $this->container->addService('DatabaseConnectionCollection', $collection);
    }

    private function loadEventListeners(): void
    {
        $collection = new EventListenerCollection($this->container->getLocator(), $this->container->getEventDispatcher());
        foreach ($this->container->getConfig()->getConfigForType('event_listeners')->getAllData() as $name => $listener) {
            $collection->addEventListener($name, $listener);
        }
        $collection->registerSubscribers();
        $this->container->addService('EventListenerCollection', $collection);
    }

    private function loadValidators(): void
    {
        $validatorCollection = new ValidatorCollection();
        foreach ($this->container->getConfig()->getConfigForType('validators')->getAllData() as $name => $config) {
            $validatorCollection->addValidator(ValidatorConfig::fromArray($name, $config));
        }
        $this->container->addService('RequestValidator', new RequestValidator($validatorCollection));
    }

    private function loadSecurityGates(): void
    {
        $gateCollection = new SecurityGateCollection();
        $this->container->addService('SecurityGateCollection', $gateCollection);
        foreach ($this->container->getConfig()->getConfigForType('security_gates')->getAllData() as $name => $config) {
            $gateCollection->addGate(SecurityGate::fromArray($name, $config));
        }
        $this->container->addService('RequestSecurityValidator', new RequestSecurityValidator($gateCollection));
    }

    private function loadFractal(): void
    {
        $this->container->addService('Fractal', new Manager());
    }


    private function loadEndpoints(): void
    {
        /**
         * @var Locator $locator
         */
        $locator = $this->container->getLocator();
        $endpointCollection = new EndpointCollection();
        $router = new Router($locator, $endpointCollection);
        foreach ($this->container->getConfig()->getConfigForType('endpoints')->getAllData() as $name => $config) {
            $router->registerEndpoint(EndpointDefinition::fromConfigArray($name, $config));
        }
        $this->container->addService('Router', $router);
        $this->container->addService('EndpointCollection', $endpointCollection);
    }

    public function loadCommands(): void
    {
        $collection = new CommandCollection();
        foreach ($this->container->getConfig()->getConfigForType('cli_commands')->getAllData() as $name => $config) {
            $commandConfig = CommandConfig::fromArray($name, $config);
            $collection->addCommand($commandConfig);
        }
        $this->container->addService('CommandCollection', $collection);
    }

    public function registerShutdownFunc(): void
    {
        register_shutdown_function(function () {
            if ($this->container->hasService('DatabaseConnectionCollection')) {
                $this->container->getService('DatabaseConnectionCollection')->closeConnections();
            }
            $error = error_get_last();
            if (function_exists('\Sentry\captureLastError') === true) {
                \Sentry\captureLastError();
            }
            if ($error && $error['type'] === E_ERROR) {
                $data = ['error' => 'An application error occurred'];
                if ($this->container->hasService('Config') && $this->container->getConfig()->getApplicationConfig()->getValue('debug') === true) {
                    $data = ['error' => $error['message'], 'file' => $error['file'], 'line' => $error['line']];
                }
                if (php_sapi_name() !== 'cli') {
                    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                    header('Content-Type: application/json');
                    echo json_encode($data);
                    exit;
                }
                echo 'ERROR: ' . $error['message'] . PHP_EOL;
                echo 'File: ' . $error['file'] . PHP_EOL;
                echo 'Line: ' . $error['line'] . PHP_EOL;
                exit(1);
            }
        });
    }
}
