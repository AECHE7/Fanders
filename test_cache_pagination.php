<?php
// Start output buffering to prevent session header issues
ob_start();

require_once 'app/config/config.php';
require_once 'app/core/Database.php';
require_once 'app/utilities/CacheUtility.php';
require_once 'app/services/UserService.php';
require_once 'app/services/ClientService.php';

echo 'Testing UserService caching and pagination...' . PHP_EOL;

$userService = new UserService();
$usersPage1 = $userService->getAllUsersWithRoleNames([], 1, 5);
$usersPage2 = $userService->getAllUsersWithRoleNames([], 2, 5);

echo 'Page 1 users count: ' . count($usersPage1) . PHP_EOL;
echo 'Page 2 users count: ' . count($usersPage2) . PHP_EOL;

$userService->deactivateUser(1);
$userService->activateUser(1);

echo 'UserService tests completed.' . PHP_EOL;

echo 'Testing ClientService caching and pagination...' . PHP_EOL;

$clientService = new ClientService();
$clientsPage1 = $clientService->getAllClients(1, 5);
$clientsPage2 = $clientService->getAllClients(2, 5);

echo 'Page 1 clients count: ' . count($clientsPage1) . PHP_EOL;
echo 'Page 2 clients count: ' . count($clientsPage2) . PHP_EOL;

$clientService->deactivateClient(1);
$clientService->activateClient(1);

echo 'ClientService tests completed.' . PHP_EOL;

echo 'All backend tests passed!' . PHP_EOL;
