<?php

namespace App\Container;

use App\Container\Exceptions\NotExistException;

class Container
{

    protected $items = [];

    public function __construct()
    {
        $this->loadServices();
    }

    protected function loadServices() {

        $this->share(\Noodlehaus\Config::class, function() {
            return new \Noodlehaus\Config(__DIR__ . '/../../config');
        });
    }

    public function add($name,callable $closure)
    {
        $this->items[$name] = $closure;
    }

    public function share($name,callable $closure)
    {
        $this->items[$name] = function() use ($closure) {

            static $service;

            if(!$service) {
                $service = $closure($this);
            }

            return $service;
        };
    }

    public function get($name)
    {
        if(!$this->has($name)) {
            //如果没有执行add方法，就会抛出注入类不存在，这里修改自动注入不存在的类
            return $this->autowrie($name);
        }

        //Config::class 由于已经在构造方法存在 name 了，所以这里直接返回
        return $this->items[$name]();
    }

    protected function autowrie($name) {

        if(!class_exists($name)) {
            throw new NotExistException("{$name}类不存在");
        
        }

        //根据反射，拿到映射的是哪一个类
        //+name: "App\Services\FileSystem 反射类很多信息
        $reflector = new \ReflectionClass($name);
        
        //拿到类之后 " 得判断它是否可实例化，排除它是 接口，抽象类，静态类，trait
        if(!$reflector->isInstantiable()) {
            throw new NotExistException("{$name}类不可实例化");
        }

        if($constructor = $reflector->getConstructor()) {
            $dependencies = $this->getDependencies($constructor);
            return $reflector->newInstanceArgs($dependencies);
        }
        //返回 new 对象
        return new $name();
    }

    public function getDependencies(\ReflectionMethod $method)
    {

        $params = $method->getParameters();

        return array_map(function(\ReflectionParameter $parameter) {

            $class = $parameter->getType();

            //如果 class 不存在
            if(!$class) {
                throw new NotExistException("{$class}类不存在");
            }

            return $this->get($class->getName());

        },$params);
    }

    public function has($name)
    {
        return isset($this->items[$name]);
    }

}