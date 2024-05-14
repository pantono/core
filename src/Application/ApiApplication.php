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
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiApplication extends Application
{
    public function bootstrap(): void
    {
        parent::bootstrap();
        $this->initSession();
    }

    public function run(): int
    {
        $this->bootstrap();
        /**
         * @var Router $router
         */
        $router = $this->container->getService('Router');
        $debug = $this->container->getConfig()->getApplicationConfig()->getValue('debug');
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
            $data = ['success' => false, 'error' => $e->getMessage()];
            if ($debug) {
                $data['file'] = $e->getFile();
                $data['line'] = $e->getLine();
            }
            $response = new JsonResponse(['data' => $data], 405);
        } catch (ApiException|HttpException|RuntimeException $e) {
            $data = ['success' => false, 'error' => $e->getMessage()];
            if ($debug) {
                $data['file'] = $e->getFile();
                $data['line'] = $e->getLine();
            }
            $code = $e->getCode();
            if ($e instanceof HttpException && $e->getStatusCode() > 0) {
                $code = $e->getStatusCode();
            }
            $statusCode = isset(JsonResponse::$statusTexts[$code]) ? $e->getCode() : 400;
            $response = new JsonResponse(['data' => $data], $statusCode);
        } catch (\Exception $e) {
            $data = ['success' => false, 'error' => $e->getMessage()];
            if ($debug) {
                $data['file'] = $e->getFile();
                $data['line'] = $e->getLine();
            }
            $response = new JsonResponse(['data' => $data], 500);
        }
        $response->send();
        $kernel->terminate($request, $response);
        return 0;
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
