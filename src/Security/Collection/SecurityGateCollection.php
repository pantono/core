<?php

namespace Pantono\Core\Security\Collection;

use Pantono\Core\Security\Model\SecurityGate;

class SecurityGateCollection
{
    /**
     * @var SecurityGate[]
     */
    private array $gates = [];

    public function addGate(SecurityGate $gate): void
    {
        $this->gates[$gate->getName()] = $gate;
    }

    public function getGateByName(string $name): ?SecurityGate
    {
        return $this->gates[$name] ?? null;
    }
}
