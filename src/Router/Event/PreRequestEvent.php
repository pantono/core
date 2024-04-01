<?php

namespace Pantono\Core\Router\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;
use Pantono\Core\Router\Model\EndpointDefinition;

class PreRequestEvent extends Event
{
    private Request $request;
    private EndpointDefinition $endpoint;

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function getEndpoint(): EndpointDefinition
    {
        return $this->endpoint;
    }

    public function setEndpoint(EndpointDefinition $endpoint): void
    {
        $this->endpoint = $endpoint;
    }
}
