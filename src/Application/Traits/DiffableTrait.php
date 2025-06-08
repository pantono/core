<?php

namespace Pantono\Core\Application\Traits;

trait DiffableTrait
{
    use ArrayableTrait;

    public function diff(self $other): array
    {
        $currentValues = $this->toArray();
        $otherValues = $other->toArray();
        $diff = [];

        foreach ($currentValues as $key => $value) {
            if (!array_key_exists($key, $otherValues) || $value !== $otherValues[$key]) {
                $diff[$key] = [
                    'new' => $value,
                    'old' => $otherValues[$key] ?? null
                ];
            }
        }

        foreach ($otherValues as $key => $value) {
            if (!array_key_exists($key, $currentValues)) {
                $diff[$key] = [
                    'new' => null,
                    'old' => $value
                ];
            }
        }

        return $diff;
    }
}
