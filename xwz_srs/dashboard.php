<?php
require_once __DIR__ . '/auth.php';
requireLogin();

$user = currentUser();
if ($user['role'] === 'Admin') {
    redirect('admin.php');
}
if ($user['role'] === 'Registrar') {
    redirect('registrar.php');
}
redirect('student.php');
