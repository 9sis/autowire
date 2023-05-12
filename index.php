<?php

use App\Services\FileSystem;

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

$app = app();
dd($app->get(FileSystem::class));