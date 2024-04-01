<?php

namespace Pantono\Core\Router\Model;

use Pantono\Contracts\Endpoint\EndpointDefinitionInterface;

class EndpointDefinition implements EndpointDefinitionInterface
{
    private string $id;
    private string $method;
    private string $route;
    private string $controller;
    private ?string $title;
    private ?string $description;
    private array $securityGates;
    private array $services;
    /**
     * @var EndpointField[]
     */
    private array $fields;

    public function __construct(string $id, string $method, string $route, string $controller, ?string $title, ?string $description, array $securityGates = [], array $services = [], array $fields = [])
    {
        $this->id = $id;
        $this->method = strtolower($method);
        $this->route = $route;
        $this->controller = $controller;
        $this->title = $title;
        $this->description = $description;
        $this->securityGates = $securityGates;
        $this->services = $services;
        $endpointFields = [];
        foreach ($fields as $name => $field) {
            $endpointFields[] = EndpointField::fromArray($name, $field);
        }
        $this->fields = $endpointFields;
    }

    public static function fromConfigArray(string $id, array $config): self
    {
        $services = $config['services'] ?? null;
        if ($services === null) {
            $services = $config['parameters'] ?? [];
        }
        $gates = $config['security_contexts'] ?? null;
        if ($gates === null) {
            $gates = $config['security_gates'] ?? [];
        }
        return new self($id, $config['method'], $config['route'], $config['controller'], $config['title'] ?? '', $config['description'] ?? '', $gates, $services, $config['fields'] ?? []);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getRoute(): string
    {
        return $this->route;
    }

    public function getController(): string
    {
        return $this->controller;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getSecurityGates(): array
    {
        return $this->securityGates;
    }

    public function getServices(): array
    {
        return $this->services;
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getNamespace(): string
    {
        $parts = explode('\\', $this->getController());
        array_pop($parts);
        return implode('\\', $parts);
    }

    public function getControllerName(): string
    {
        $parts = explode('\\', $this->getController());
        return array_pop($parts);
    }
}
