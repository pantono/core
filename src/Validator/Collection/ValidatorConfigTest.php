<?php

namespace Pantono\Core\Validator\Collection;

use PHPUnit\Framework\TestCase;

class ValidatorConfigTest extends TestCase
{
    public function testValidatorCreates(): void
    {
        $expected = new ValidatorConfig('test', '\Some\Class', [], []);

        $this->assertEquals($expected, ValidatorConfig::fromArray('test', ['class' => '\Some\Class']));
    }

    public function testValidatorCreatesWithServices(): void
    {
        $expected = new ValidatorConfig('test', '\Some\Class', ['@Service1', '@Service2'], []);

        $this->assertEquals($expected, ValidatorConfig::fromArray('test', ['class' => '\Some\Class', 'services' => ['@Service1', '@Service2']]));
    }

    public function testValidatorCreatesWithOptions(): void
    {
        $expected = new ValidatorConfig('test', '\Some\Class', [], ['test' => 'value']);

        $this->assertEquals($expected, ValidatorConfig::fromArray('test', ['class' => '\Some\Class', 'options' => ['test' => 'value']]));
    }
}
