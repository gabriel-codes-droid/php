<?php
require_once __DIR__ . '/auth.php';

if (currentUser()) {
    redirect('dashboard.php');
}

redirect('login.php');
