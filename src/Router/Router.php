<?php

namespace Pantono\Core\Router;

use Pantono\Core\Router\Model\EndpointDefinition;
use Pantono\Core\Router\Model\EndpointCollection;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Pantono\Core\Router\Endpoint\AbstractEndpoint;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Pantono\Container\Traits\ContainerAware;
use League\Fractal\Resource\ResourceAbstract;
use Pantono\Core\Router\Event\PreRequestEvent;
use Pantono\Container\Service\Locator;
use Pantono\Core\Application\Exception\RequestException;
use Pantono\Contracts\Router\RouterInterface;
use Pantono\Contracts\Endpoint\EndpointDefinitionInterface;
use Pantono\Core\Security\RequestSecurityValidator;
use Pantono\Core\Validator\RequestValidator;

class Router implements ControllerResolverInterface, RouterInterface
{
    use ContainerAware;

    private Locator $locator;
    private EndpointCollection $collection;

    public function __construct(Locator $locator, EndpointCollection $endpointCollection)
    {
        $this->locator = $locator;
        $this->collection = $endpointCollection;
    }

    public function registerEndpoint(EndpointDefinitionInterface $endpoint): void
    {
        $this->collection->addEndpoint($endpoint);
    }


    public function getController(Request $request): callable|false
    {
        if ($request->getContentTypeFormat() === 'json') {
            $requestJson = json_decode($request->getContent(false), true, 512, JSON_THROW_ON_ERROR);
            $_POST = $requestJson;
        }

        $uri = $request->getPathInfo();
        [$endpoint, $params] = $this->collection->getEndpoint($request->getMethod(), $uri);
        foreach ($params as $field => $value) {
            $_GET[$field] = $value;
        }
        $request = Request::createFromGlobals();
        return function () use ($endpoint, $request) {
            $instance = $this->createControllerInstance($endpoint);
            $instance->setRequest($request);
            if ($this->locator->getContainer()->has('service_Session')) {
                $instance->setSession($this->locator->getContainer()['service_Session']);
            }
            $instance->setSecurityContext($this->locator->getContainer()->getSecurityContext());
            $instance->setEndpoint($endpoint);
            $event = new PreRequestEvent();
            $event->setEndpoint($endpoint);
            $event->setRequest($request);
            $this->getContainer()->getEventDispatcher()->dispatch($event);


            /**
             * @var RequestSecurityValidator $securityGateValidator
             */
            $securityGateValidator = $this->locator->getContainer()->getService('RequestSecurityValidator');
            $securityGateValidator->validateRequest($endpoint, $request);
            /**
             * @var RequestValidator $validator
             */
            $validator = $this->locator->getContainer()->getService('RequestValidator');
            $validationResult = $validator->validateRequest($endpoint, $request);
            if ($validationResult->isOk() === false) {
                throw new RequestException('Request validation failed', $validationResult->getFieldErrors(), 400);
            }
            $parameters = $validationResult->getProcessedFieldInput();

            $response = null;
            $responseData = $instance->processRequest($parameters);
            if ($responseData instanceof Response) {
                $response = $responseData;
            } elseif ($responseData instanceof ResourceAbstract) {
                $requestedIncludes = explode(',', $request->get('include', ''));
                $this->getContainer()->getService('Fractal')->parseIncludes($requestedIncludes);

                $requestExcludes = explode(',', $request->get('exclude', ''));
                $this->getContainer()->getService('Fractal')->parseExcludes($requestExcludes);
                $response = new JsonResponse($this->getContainer()->getService('Fractal')->createData($responseData)->toArray());
            } elseif (is_array($responseData)) {
                $response = new JsonResponse($responseData);
            }
            return $response;
        };
    }

    private function createControllerInstance(EndpointDefinition $endpoint): AbstractEndpoint
    {
        if (!class_exists($endpoint->getController())) {
            throw new \RuntimeException('Controller ' . $endpoint->getController() . ' does not exist');
        }
        if (empty($endpoint->getServices())) {
            return $this->locator->getClassAutoWire($endpoint->getController());
        }
        $class = new \ReflectionClass($endpoint->getController());
        if ($class->isSubclassOf(AbstractEndpoint::class) === false) {
            throw new \Exception('Controller is not an instance of AbstractEndpoint');
        }
        $services = [];
        foreach ($endpoint->getServices() as $service) {
            $services[] = $this->locator->loadDependency($service);
        }
        return $class->newInstanceArgs($services);
    }
}
