<?php

use App\Entry;

// chdir('../');
chdir(dirname(__DIR__)); // change working dir to one level up (the root)
require_once './app/init.php';
new Entry();
