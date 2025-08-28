<?php

namespace Pantono\Core\Application;

use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Pantono\Core\Application\Exception\ApiException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use RuntimeException;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;
use Pantono\Database\Connection\ConnectionCollection;
use Pantono\Database\Repository\MysqlRepository;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Session;
use Pantono\Core\Router\Router;
use Pantono\Container\StaticContainer;
use Symfony\Component\HttpFoundation\Response;
use Pantono\Core\Application\Traits\TwigRendererTrait;

class WebApplication extends Application
{
    use TwigRendererTrait;

    public function bootstrap(): void
    {
        parent::bootstrap();
        $this->initSession();
        $this->initShutdownHandler();
    }


    public function run(): int
    {
        $this->bootstrap();
        /**
         * @var Router $router
         */
        $router = $this->container->getService('Router');
        $debug = $this->container->getConfig()->getApplicationConfig()->getBooleanValue('debug');
        $kernel = new HttpKernel(
            $this->container->getEventDispatcher(),
            $router,
            new RequestStack(),
            new ArgumentResolver()
        );
        $request = Request::createFromGlobals();
        try {
            $response = $kernel->handle($request);
        } catch (MethodNotAllowedHttpException $e) {
            if (function_exists('\Sentry\captureException') === true) {
                \Sentry\captureException($e);
            }
            $response = new Response($this->render('error.twig', ['error' => ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getFile(), 'debug' => $debug]]), 405);
        } catch (ApiException|HttpException|RuntimeException $e) {
            $code = $e->getCode();
            if ($e instanceof HttpException && $e->getStatusCode() > 0) {
                $code = $e->getStatusCode();
            }
            $statusCode = isset(JsonResponse::$statusTexts[$code]) ? $code : 400;
            $response = new Response($this->render('error.twig', ['error' => ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getFile(), 'debug' => $debug]]), $statusCode);
        } catch (\Exception $e) {
            if (function_exists('\Sentry\captureException') === true) {
                \Sentry\captureException($e);
            }
            $response = new Response($this->render('error.twig', ['error' => ['message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getFile(), 'debug' => $debug]]), 500);
        }
        $response->send();
        $kernel->terminate($request, $response);
        return 0;
    }

    private function initShutdownHandler(): void
    {
        register_shutdown_function(function () {
            if (function_exists('\Sentry\captureLastError') === true) {
                \Sentry\captureLastError();
            }
            $error = error_get_last();
            if (isset($error['type']) && $error['type'] === E_ERROR) {
                $debug = $this->container->getConfig()->getApplicationConfig()->getBooleanValue('debug');
                $options = [];
                if ($debug) {
                    $options = ['error' => $error['message'], 'file' => $error['file'], 'line' => $error['line']];
                }
                header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
                echo $this->render('error.twig', $options);
                die(1);
            }
        });
    }

    private function initSession(): void
    {
        /**
         * @var ConnectionCollection $connectionCollection
         */
        $connectionCollection = $this->container->getService('DatabaseConnectionCollection');
        $db = $connectionCollection->getConnectionForParent(MysqlRepository::class);
        $handler = new PdoSessionHandler($db->getConnection(), ['db_table' => 'sessions', 'lock_mode' => PdoSessionHandler::LOCK_NONE]);
        $storage = new NativeSessionStorage(['use_strict_mode' => 0, 'gc_maxlifetime' => 86400], $handler);
        $session = new Session($storage);
        $session->start();
        $this->container->addService('Session', $session);
        StaticContainer::setSession($session);
    }
}
