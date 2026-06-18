<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('login.php');
}

verifyCsrf();
$_SESSION = [];
session_destroy();
redirect('login.php');
