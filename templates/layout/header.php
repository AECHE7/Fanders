<?php
/**
 * Header template for the Fanders Microfinance Loan Management System
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Fanders Microfinance Loan Management System - Efficient loan processing and client management">
    <meta name="keywords" content="microfinance, loans, payments, clients, management">
    <meta name="author" content="Fanders Microfinance">
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken ?? '') ?>">
    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' . APP_NAME : APP_NAME ?></title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Custom Stylesheets -->
    <!-- Custom Stylesheets -->
    <link href="<?= APP_URL ?>/public/assets/css/style.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/style.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/feather-icons/dist/feather.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/interactions.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/interactions.css') ?>">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/sidebar.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/sidebar.css') ?>">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/sidebar-enhanced.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/sidebar-enhanced.css') ?>">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/navigation-enhanced.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/navigation-enhanced.css') ?>">
    <link rel="stylesheet" href="<?= APP_URL ?>/public/assets/css/layout-fix.css?v=<?= filemtime(BASE_PATH . '/public/assets/css/layout-fix.css') ?>">    <!-- Favicon -->
    <link rel="icon" href="<?= APP_URL ?>/assets/favicon.png" type="image/png" sizes="32x32">
    <link rel="apple-touch-icon" href="<?= APP_URL ?>/assets/favicon.png">
</head>
<body>
    <header class="navbar sticky-top flex-md-nowrap p-0 border-bottom bg-white shadow-sm">
        <!-- Sidebar Toggle Button for Desktop -->
        <button class="btn btn-link d-none d-md-flex align-items-center justify-content-center sidebar-toggle" id="sidebarToggle" style="width: 56px; height: 56px;" title="Toggle Sidebar">
            <i data-feather="menu" class="text-primary" style="width: 20px; height: 20px;"></i>
        </button>

        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3 d-flex align-items-center text-decoration-none" href="<?= APP_URL ?>/public/dashboard/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-dollar-sign me-2 text-primary">
                <path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
            </svg>
            <span class="fw-bold text-primary d-none d-lg-inline"><?= APP_NAME ?></span>
        </a>

        <!-- Mobile Sidebar Toggle -->
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- User Menu -->
        <?php include_once BASE_PATH . '/templates/layout/user_menu.php'; ?>
    </header>
    
    <!-- Layout wrapper holds sidebar and main content -->
    <div class="layout">


