<?php
// ============================================================================
// Bader Phones / بدر للهواتف - Logout Handler (logout.php)
// Destroys session securely and redirects to login.php
// ============================================================================

require_once __DIR__ . '/auth.php';

destroyUserSession();

header("Location: login.php?logged_out=1");
exit;
