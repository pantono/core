<?php

namespace Pantono\Core\Decorator;

use League\Fractal\TransformerAbstract;

class GenericArrayDecorator extends TransformerAbstract
{
    public function transform(array $data): array
    {
        return $data;
    }
}
