<?php

namespace App\Container;

use App\Container\Exceptions\NotExistException;

class Container
{

    protected $items = [];

    public function add($name,callable $closure)
    {
        # code...
        $this->items[$name] = $closure;
    }

    public function get($name)
    {
        if(!$this->has($name)) {
            throw new NotExistException("{$name} not found in container");
        }
        return $this->items[$name]();
    }

    public function has($name)
    {
        return isset($this->items[$name]);
    }

}