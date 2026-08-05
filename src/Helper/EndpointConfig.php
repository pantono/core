<?php

namespace Pantono\Core\Helper;

use Pantono\Core\Router\Model\EndpointDefinition;

class EndpointConfig
{
    private string $name;
    private string $controller;
    private string $route;
    private string $method = 'GET';
    private array $securityGates = [];
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


    public function addSecurityGate(string $name): self
    {
        $this->securityGates[] = $name;
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

    public function makePageable(): self
    {
        $this->addIntField('page', 'Page', true);
        $this->addIntField('per_page', 'Per Page', false);
        return $this;
    }

    public function addStringField(string $name, ?string $label = null, bool $required = false): self
    {
        return $this->addField('string', $name, $label, $required);
    }

    public function addBoolField(string $name, ?string $label = null, bool $required = false): self
    {
        return $this->addField('boolean', $name, $label, $required);
    }

    public function addFloatField(string $name, ?string $label = null, bool $required = false): self
    {
        return $this->addField('float', $name, $label, $required);
    }

    public function addIntField(string $name, ?string $label = null, bool $required = false): self
    {
        return $this->addField('int', $name, $label, $required);
    }

    public function addDateField(string $name, ?string $label = null, bool $required = false): self
    {
        $this->fields[$name] = [
            'type' => 'date',
            'required' => $required,
            'label' => $label ?: $name,
            'cast' => \DateTimeImmutable::class,
            'validators' => [
                'ValidDate' => ['date_format' => 'Y-m-d']
            ]
        ];
        return $this;
    }

    public function addDateTimeField(string $name, ?string $label = null, bool $required = false): self
    {
        $this->fields[$name] = [
            'type' => 'datetime',
            'required' => $required,
            'label' => $label ?: $name,
            'cast' => \DateTimeImmutable::class,
            'validators' => [
                'ValidDate' => ['date_format' => 'Y-m-d']
            ]
        ];
        return $this;
    }

    public function addCastField(string $name, string $cast, ?string $label = null, bool $required = false): self
    {
        if (!class_exists($cast)) {
            throw new \RuntimeException(sprintf('Cast class %s does not exist', $cast));
        }
        $this->fields[$name] = [
            'type' => 'integer',
            'cast' => $cast,
            'required' => $required,
            'label' => $label ?: $name
        ];
        return $this;
    }

    public function setFields(array $fields): self
    {
        $this->fields = $fields;
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
            'route' => $this->route,
            'name' => $this->name
        ];
        if ($this->controller) {
            $data['controller'] = $this->controller;
        }
        if (!empty($this->dependencies)) {
            $data['dependencies'] = $this->dependencies;
        }
        if (!empty($this->securityGates)) {
            $data['security_gates'] = $this->securityGates;
        }
        if (!empty($this->fields)) {
            $data['fields'] = $this->fields;
        }

        return $data;
    }

    public static function __set_state(array $data): object
    {
        $class = new EndpointConfig($data['name']);
        $class->controller = $data['controller'];
        $class->method = $data['method'];
        $class->route = $data['route'];
        $class->securityGates = $data['securityGates'];
        $class->fields = $data['fields'];
        $class->dependencies = $data['dependencies'];
        return $class;
    }
}
