<?php
require_once 'app/config/config.php';
require_once 'app/utilities/CacheUtility.php';

$cache = new CacheUtility();
$result = $cache->remember('test', 60, function() { return 'hello'; });
echo 'Result: ' . $result . PHP_EOL;
?>
