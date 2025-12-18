<?php
require_once 'config.php';

// Hapus semua session
session_unset();
session_destroy();

// Redirect ke login dengan pesan
header('Location: login.php');
exit;
?>