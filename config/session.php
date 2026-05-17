<?php
// ============================================================
//  config/session.php
//  Include this at the TOP of every page that requires login.
//
//  What it does:
//   - Starts the PHP session
//   - Checks if the user is logged in (session has user_id)
//   - If NOT logged in → kicks them back to the login page
//
//  Without this, anyone who knows the URL of dashboard.php
//  could open it without logging in first.
// ============================================================

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2) . 'index.php');
    exit;
}