<?php
require_once __DIR__ . '/auth.php';
$pageTitle = $pageTitle ?? 'XWZ School SRS';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($pageTitle); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <h1>XWZ SRS</h1>
        <p>Student Registration System</p>
        <?php if (currentUser()): ?>
            <div class="profile">
                <strong><?php echo e(currentUser()['firstName'] . ' ' . currentUser()['lastName']); ?></strong>
                <span><?php echo e(currentUser()['role']); ?></span>
            </div>
            <a class="<?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">Dashboard</a>
            <?php if (in_array(currentUser()['role'], ['Admin', 'Registrar'], true)): ?>
                <a class="<?php echo basename($_SERVER['PHP_SELF']) === 'registrar.php' ? 'active' : ''; ?>" href="registrar.php">Registrar</a>
            <?php endif; ?>
            <?php if (currentUser()['role'] === 'Admin'): ?>
                <a class="<?php echo basename($_SERVER['PHP_SELF']) === 'admin.php' ? 'active' : ''; ?>" href="admin.php">Admin</a>
            <?php endif; ?>
            <?php if (currentUser()['role'] === 'Student'): ?>
                <a class="<?php echo basename($_SERVER['PHP_SELF']) === 'student.php' ? 'active' : ''; ?>" href="student.php">Student</a>
            <?php endif; ?>
            <form method="post" action="logout.php">
                <?php echo csrfField(); ?>
                <button class="ghost" type="submit">Logout</button>
            </form>
        <?php else: ?>
            <a class="<?php echo basename($_SERVER['PHP_SELF']) === 'login.php' ? 'active' : ''; ?>" href="login.php">Login</a>
            <a class="<?php echo basename($_SERVER['PHP_SELF']) === 'signup.php' ? 'active' : ''; ?>" href="signup.php">Signup</a>
        <?php endif; ?>
    </aside>
    <main class="main">
        <?php flashMessages(); ?>
