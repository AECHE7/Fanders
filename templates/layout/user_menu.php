<?php
/**
 * User Menu Dropdown template for the Fanders Microfinance Loan Management System
 * This file contains the user dropdown menu to separate it from the main header layout
 */

// Get current user and role (safe defaulting)
$currentUser = $auth->getCurrentUser();

// Use 'role' string directly
$userRole = isset($currentUser['role']) ? $currentUser['role'] : '';
// For display, format role if needed
$roleName = isset($currentUser['role_display']) ? $currentUser['role_display'] : ucfirst(str_replace('-', ' ', $userRole));

// Parse first and last name from 'name'
$fullName = isset($currentUser['name']) ? $currentUser['name'] : '';
$nameParts = explode(' ', $fullName, 2);
$firstName = $nameParts[0] ?? '';
$lastName = $nameParts[1] ?? '';
$initials = ($firstName ? substr($firstName, 0, 1) : '') . ($lastName ? substr($lastName, 0, 1) : '');

// For username, prefer 'username', fallback to 'email'
$usernameDisplay = $currentUser['username'] ?? ($currentUser['email'] ?? '');
?>

<div class="navbar-nav">
    <div class="nav-item dropdown">
        <a class="nav-link dropdown-toggle px-3 d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
// Make sure we can access the global $auth even when included from within a function scope
global $auth;

// Get current user and role (safe defaulting)
$currentUser = [];
if (isset($auth) && is_object($auth) && method_exists($auth, 'getCurrentUser')) {
    $currentUser = $auth->getCurrentUser() ?: [];
}
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                    <span class="fw-bold"><?= htmlspecialchars($initials) ?></span>
                </div>
                <span class="d-none d-md-inline"><?= htmlspecialchars($firstName) ?></span>
            </div>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="navbarDropdown">
            <li class="dropdown-header px-3 py-2">
                <div class="d-flex align-items-center">
                    <i data-feather="user" class="me-2 text-muted" style="width: 16px; height: 16px;"></i>
                    <div>
                        <small class="text-muted">Signed in as</small><br>
                        <strong class="text-dark"><?= htmlspecialchars($usernameDisplay) ?></strong>
$usernameDisplay = $currentUser['username'] ?? ($currentUser['email'] ?? 'User');
                </div>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li class="px-3 py-1">
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">
                    <i data-feather="user-check" class="me-1" style="width: 14px; height: 14px;"></i>
                    <?= htmlspecialchars($roleName) ?>
                </span>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li>
                <a class="dropdown-item d-flex align-items-center py-2" href="<?= APP_URL ?>/public/users/view.php<?= isset($currentUser['id']) ? ('?id=' . (int)$currentUser['id']) : '' ?>">
                    <i data-feather="settings" class="me-2" style="width: 16px; height: 16px;"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li>
                <a class="dropdown-item d-flex align-items-center py-2 text-danger" href="<?= APP_URL ?>/public/logout.php">
                    <i data-feather="log-out" class="me-2" style="width: 16px; height: 16px;"></i>
                    <span>Sign out</span>
                </a>
            </li>
        </ul>
    </div>
</div>
