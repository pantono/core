<?php

namespace Pantono\Core\Helper;

class EndpointConfigCollection
{
    /**
     * @var EndpointConfig[]
     */
    private array $endpoints = [];


    public function addEndpoint(string $name): EndpointConfig
    {
        $config = new EndpointConfig($name);
        $this->endpoints[$name] = $config;
        return $config;
    }

    public function toArray(): array
    {
        return $this->endpoints;
    }

    public function __toArray(): array
    {
        return $this->endpoints;
    }
}
