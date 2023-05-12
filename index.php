<?php

use App\Controllers\HomeController;
use App\Services\Cache;
use App\Services\FileSystem;
use Noodlehaus\Config;

require 'vendor/autoload.php';


// $config = new \Noodlehaus\Config(__DIR__ . '/config');
// $cache = new \App\Services\Cache($config);



// $c = new \App\Controllers\HomeController($config, $cache);

// $c->index();

// $container = new \App\Container\Container();
// $container->add('config', function() {
//     return new \Noodlehaus\Config(__DIR__ . '/config');
// });

// $config = $container->get('config');
// dump($config);

// $app = app();
// dd($app->get(Cache::class));

// dd(app(Cache::class));


// $c = new HomeController(app(Config::class), app(Cache::class));
// $c->index();


// app(HomeController::class)->index();



dd(app(Config::class));