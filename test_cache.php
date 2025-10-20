<?php
require_once 'app/config/config.php';
require_once 'app/utilities/CacheUtility.php';

$cache = new CacheUtility();
$result = $cache->remember('test', function() { return 'hello'; }, 60);
echo 'Result: ' . $result . PHP_EOL;
?>
