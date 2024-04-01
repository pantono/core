<?php
namespace Pantono\Core\Application\Endpoint;

use League\Fractal\Resource\ResourceAbstract;
use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Core\Router\Endpoint\AbstractEndpoint;

class Ping extends AbstractEndpoint
{
    public function processRequest(ParameterBag $parameters): ResourceAbstract|array
    {
        return ['pong' => time(), 'params' => $parameters->all()];
    }
}
