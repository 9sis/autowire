<?php


namespace App\Controllers;

use App\Services\Cache;
use Noodlehaus\Config;

class HomeController
{
    public function __construct(protected Config $config, protected Cache $cache)
    {
        
    }
    

    public function index()
    {
        dump($this->cache, $this->config);
    }
}
