<?php

namespace Pantono\Core\Application\Traits;

trait ArrayableTrait
{
    public function toArray(): array
    {
        $vars = [];
        foreach (get_object_vars($this) as $key => $value) {
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d H:i:s');
            }
            $vars[$key] = $value;
        }
        return $vars;
    }
}
