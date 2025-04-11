<?php
require_once 'includes/functions.php';

// Destroy the session
session_destroy();

// Redirect to homepage
header('Location: index.php');
exit();
?>
