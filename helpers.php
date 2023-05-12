<?php

if(!function_exists('app')) {
    
    function app($name =null)
    {
        static $container;

        if(!$container) {
            $container = new App\Container\Container();
        }

        if($name) {
            return $container->get($name);
        }

        return $container;
    }
}