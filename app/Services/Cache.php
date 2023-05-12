<?php

namespace App\Services;

use Noodlehaus\Config;

class Cache
{
    public function __construct(protected FileSystem $fileSystem)
    {
        
    }
}