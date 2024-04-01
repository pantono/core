<?php

namespace Pantono\Core\Application;

class CliApplication extends Application
{
    public function run(): int
    {
        $this->bootstrap();
        $application = new \Symfony\Component\Console\Application();
        foreach ($this->container->getService('CommandCollection')->getCommands() as $commandConfig) {
            if (class_exists($commandConfig->getClass()) === false) {
                continue;
            }
            if (empty($commandConfig->getServices())) {
                $application->add($this->container->getLocator()->getClassAutoWire($commandConfig->getClass()));
            } else {
                $application->add($this->container->getLocator()->loadClass($commandConfig->getClass(), $commandConfig->getServices()));
            }
        }
        return $application->run();
    }
}
