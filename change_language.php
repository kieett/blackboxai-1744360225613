<?php
session_start();

// Check if the language parameter is set in the URL
if (isset($_GET['lang'])) {
    // Set the session variable for language
    $_SESSION['lang'] = $_GET['lang'];
}

// Redirect back to the referring page
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
