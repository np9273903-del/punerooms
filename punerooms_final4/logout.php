<?php
require_once 'db.php';
start_session();
logout_user();
header('Location: login.php?msg=' . urlencode('Logged out successfully. See you soon! 👋'));
exit;
?>
