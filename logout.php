<?php
/**
 * Typoria Blog Platform
 * Logout Handler
 */

// We need to define a placeholder for functions from functions.php
// to prevent redeclaration errors when auth.php tries to include functions.php
define('FUNCTIONS_ALREADY_LOADED', true);

// Now include auth.php directly to get the logout_user function
require_once 'includes/auth.php';

// Log the user out using the function from auth.php
logout_user();

// Redirect to home page
header("Location: index.php");
exit();