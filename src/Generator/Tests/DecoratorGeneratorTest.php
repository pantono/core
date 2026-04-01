<?php

namespace Pantono\Core\Generator\Tests;

use PHPUnit\Framework\TestCase;
use Pantono\Core\Generator\DecoratorGenerator;
use Pantono\Core\Generator\Tests\Model\TestModel;

class DecoratorGeneratorTest extends TestCase
{
    public function testGenerateDecorator()
    {
        $generator = new DecoratorGenerator(TestModel::class);
        $output = $generator->generate('App\Decorator');
        $this->assertEquals($output, file_get_contents(__DIR__ . '/Data/test-model-expected.txt'));
    }
}
