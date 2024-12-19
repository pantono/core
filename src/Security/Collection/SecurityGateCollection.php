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

    /**
     * @return SecurityGate[]
     */
    public function getGlobalGates(): array
    {
        $global = [];
        foreach ($this->gates as $gate) {
            if ($gate->isGlobal()) {
                $global[] = $gate;
            }
        }
        return $global;
    }
}
