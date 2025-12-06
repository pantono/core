<?php

namespace Pantono\Core\Helper;

use Pantono\Core\Router\Model\EndpointDefinition;

class EndpointConfig
{
    private string $name;
    private string $controller;
    private string $route;
    private string $method = 'GET';
    private array $securityContexts = [];
    private array $fields = [];
    private array $dependencies = [];

    public function __construct(string $name)
    {

        $this->name = $name;
    }

    public function setController(string $controller): self
    {
        $this->controller = $controller;
        return $this;
    }


    public function setMethod(string $method): self
    {
        $this->method = $method;
        return $this;
    }

    public function setRoute(string $route): self
    {
        $this->route = $route;
        return $this;
    }


    public function addSecurityContext(string $name): self
    {
        $this->securityContexts[] = $name;
        return $this;
    }

    public function addDependency(string $name): self
    {
        $this->dependencies[] = $name;
        return $this;
    }

    public function addField(string $type, string $name, ?string $label = null, bool $required = false): self
    {
        $this->fields[$name] = [
            'type' => $type,
            'required' => $required,
            'label' => $label ?: $name
        ];
        return $this;
    }

    public function toEndpointDefinition(): EndpointDefinition
    {
        return EndpointDefinition::fromConfigArray($this->name, $this->toArray());
    }

    public function toArray(): array
    {
        $data = [
            'method' => $this->method,
            'route' => $this->route
        ];
        if ($this->controller) {
            $data['controller'] = $this->controller;
        }
        if (!empty($this->dependencies)) {
            $data['dependencies'] = $this->dependencies;
        }
        if (!empty($this->securityContexts)) {
            $data['security_contexts'] = $this->securityContexts;
        }
        if (!empty($this->fields)) {
            $data['fields'] = $this->fields;
        }

        return $data;
    }
}
