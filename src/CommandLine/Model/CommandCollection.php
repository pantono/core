<?php

namespace Pantono\Core\CommandLine\Model;

class CommandCollection
{
    /**
     * @var CommandConfig[]
     */
    private array $commands = [];

    public function addCommand(CommandConfig $config): void
    {
        $this->commands[] = $config;
    }

    public function getCommands(): array
    {
        return $this->commands;
    }

    public function getCommandByByName(string $name): ?CommandConfig
    {
        foreach ($this->commands as $command) {
            if ($command->getName() === $name) {
                return $command;
            }
        }
        
        return null;
    }
}
