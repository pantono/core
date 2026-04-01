<?php

namespace Pantono\Core\Generator\Tests\Model;

class TestModel
{
    private int $id = 1;
    private string $name = 'Test';
    private \DateTimeInterface $date;
    private float $float = 0.52;

    public function __construct()
    {
        $this->date = new \DateTimeImmutable('2025-01-01 00:00:00');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): void
    {
        $this->date = $date;
    }

    public function getFloat(): float
    {
        return $this->float;
    }

    public function setFloat(float $float): void
    {
        $this->float = $float;
    }
}
