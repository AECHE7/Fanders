<?php
require_once '../app/config/config.php';

// Start output buffering
ob_start();

// Include all required files
function autoload($className) {
    // Define the directories to look in
    $directories = [
        'app/core/',
        'app/models/',
        'app/services/',
        'app/utilities/'
    ];
    
    // Try to find the class file
    foreach ($directories as $directory) {
        $file = BASE_PATH . '/' . $directory . $className . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}
spl_autoload_register('autoload');
// Instantiate the service
$bookService = new BookService();

// Fetch archived books
$archivedBooks = $bookService->getArchivedBooks();

// Check if any archived books found
if (!$archivedBooks || !is_array($archivedBooks)) {
    echo "No archived books found or an error occurred.";
    exit;
}

// Print list of archived books
echo "&lt;h2&gt;Archived Books&lt;/h2&gt;";
echo "&lt;ul&gt;";

foreach ($archivedBooks as $book) {
    $title = htmlspecialchars($book['title']);
    $author = htmlspecialchars($book['author']);
    $category = htmlspecialchars($book['category_name'] ?? 'Unknown');
    $deletedAt = htmlspecialchars($book['deleted_at'] ?? 'Unknown');

    echo "&lt;li&gt;";
    echo "&lt;strong&gt;Title:&lt;/strong&gt; $title &lt;br&gt;";
    echo "&lt;strong&gt;Author:&lt;/strong&gt; $author &lt;br&gt;";
    echo "&lt;strong&gt;Category:&lt;/strong&gt; $category &lt;br&gt;";
    echo "&lt;strong&gt;Archived On:&lt;/strong&gt; $deletedAt";
    echo "&lt;/li&gt;";
}

echo "&lt;/ul&gt;";

error_reporting(E_ALL);
ini_set('display_errors', 1);

