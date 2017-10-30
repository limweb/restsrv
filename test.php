<?php 
require_once __DIR__.'/vendor/autoload.php'; // Autoload files using Composer autoload
use Servit\SayHello;
echo SayHello::world();
 // Go to the terminal (or create a PHP web server inside "tests" dir) and type: 
// php tests/test.php