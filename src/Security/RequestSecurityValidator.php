<?php

namespace Pantono\Core\Security;

use Pantono\Core\Security\Collection\SecurityGateCollection;
use Symfony\Component\HttpFoundation\Request;
use Pantono\Core\Router\Model\EndpointDefinition;
use Pantono\Container\Traits\ContainerAware;
use Pantono\Core\Security\Model\SecurityGate;
use Pantono\Contracts\Security\Gate\SecurityGateInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class RequestSecurityValidator
{
    use ContainerAware;

    private SecurityGateCollection $collection;

    public function __construct(?SecurityGateCollection $collection = null)
    {
        if ($collection === null) {
            $collection = new SecurityGateCollection();
        }
        $this->collection = $collection;
    }

    public function validateRequest(EndpointDefinition $endpoint, Request $request): void
    {
        foreach ($endpoint->getSecurityGates() as $config) {
            if (is_string($config)) {
                $gateName = $config;
                $fields = [];
            } else {
                $keys = array_keys($config);
                $gateName = $keys[0];
                $fields = $config[$gateName];
            }
            $this->runValidation((string)$gateName, $request, $endpoint, $fields);
        }
        foreach ($this->collection->getGlobalGates() as $gate) {
            $this->runValidation($gate->getName(), $request, $endpoint, []);
        }
    }

    private function runValidation(string $gateName, Request $request, EndpointDefinition $endpoint, array $fields): void
    {
        $gate = $this->collection->getGateByName($gateName);
        if (!$gate) {
            throw new \RuntimeException('Security gate ' . $gateName . ' does not exist');
        }
        foreach ($gate->getDepends() as $dependent) {
            $this->runValidation($dependent, $request, $endpoint, []);
        }
        $gateClass = $this->buildGate($gate);
        $gateClass->isValid($request, $endpoint, new ParameterBag($fields), $this->getContainer()->getService('Session'));
    }

    private function buildGate(SecurityGate $gate): SecurityGateInterface
    {
        return $this->getContainer()->getLocator()->getClassAutoWire($gate->getClass());
    }
}
