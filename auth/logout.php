<?php
require_once __DIR__ . '/../includes/functions.php';

// Start session if not already started
ensure_session_started();

// Destroy the session
session_unset();
session_destroy();

// Redirect to the login page
header("Location: /carwash-appoinment/");
exit;