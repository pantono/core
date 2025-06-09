<?php

namespace Pantono\Core\Application\Traits;

use Symfony\Component\HttpFoundation\Request;
use Pantono\Utilities\StringUtilities;

trait FillableTrait
{
    public static function fillFromRequest(Request $request): self
    {
        if ($request->getMethod() === 'POST' || $request->getMethod() === 'PUT') {
            $params = $request->request;
        } else {
            $params = $request->query;
        }
        $instance = new self;
        foreach (get_object_vars($instance) as $key => $value) {
            $snakeCase = StringUtilities::snakeCase($key);
            $setter = 'set' . ucfirst($snakeCase);
            $hasSetter = method_exists($instance, $setter);
            if (!$hasSetter) {
                continue;
            }
            if ($params->has($snakeCase)) {
                $instance->$setter($params->get($snakeCase));
            }
            if ($params->has($key)) {
                $instance->$setter($params->get($key));
            }
        }
    }
}
