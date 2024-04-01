<?php

namespace Pantono\Core\Router\Model;

use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use FastRoute\Dispatcher;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Pantono\Contracts\Endpoint\EndpointDefinitionInterface;

class EndpointCollection implements \ArrayAccess
{
    /**
     * @var EndpointDefinitionInterface[]
     */
    private array $endpoints = [];

    public function addEndpoint(EndpointDefinitionInterface $endpoint): void
    {
        $this->endpoints[] = $endpoint;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->endpoints[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->endpoints[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException('Endpoint collection is read only');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException('Endpoint collection is read only');
    }

    public function getEndpointByKey(string $key): ?EndpointDefinitionInterface
    {
        foreach ($this->endpoints as $endpoint) {
            if ($endpoint->getId() === $key) {
                return $endpoint;
            }
        }
        
        return null;
    }

    public function getEndpoint(string $method, string $uri): array
    {
        $method = strtolower($method);
        $dispatcher = simpleDispatcher(
            function (RouteCollector $r) {
                foreach ($this->endpoints as $endpoint) {
                    $r->addRoute($endpoint->getMethod(), $endpoint->getRoute(), $endpoint);
                }
            }
        );
        $result = $dispatcher->dispatch($method, $uri);
        $status = $result[0];
        if ($status === Dispatcher::NOT_FOUND) {
            throw new HttpException(404, '404 Not Found');
        }

        $handler = $result[1];
        if ($status === Dispatcher::METHOD_NOT_ALLOWED) {
            throw new MethodNotAllowedHttpException($handler, 'Method not allowed');
        }
        $params = $result[2];

        return [$handler, $params];
    }

    /**
     * @return EndpointDefinitionInterface[]
     */
    public function getAllEndpoints(): array
    {
        return $this->endpoints;
    }
}