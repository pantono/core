<?php

namespace Pantono\Core\Application\Helper;

use Pagerfanta\Adapter\AdapterInterface;

class PaginationArrayAdapter implements AdapterInterface
{
    private array $data;

    /**
     * @var int<0,max>
     */
    private int $total;

    /**
     * @param array $data
     * @param int<0,max> $total
     */
    public function __construct(array $data, int $total)
    {
        $this->data = $data;
        $this->total = $total;
    }

    public function getNbResults(): int
    {
        return $this->total;
    }

    public function getSlice(int $offset, int $length): array
    {
        return $this->data;
    }
}
