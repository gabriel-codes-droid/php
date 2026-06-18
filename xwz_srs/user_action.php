<?php
require_once __DIR__ . '/auth.php';
requireRole(['Admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('admin.php');
}

verifyCsrf();

$action = $_POST['action'] ?? '';
$id = trim($_POST['id'] ?? '');
$users = allUsers();

if ($id === currentUser()['id'] && in_array($action, ['role', 'delete'], true)) {
    setError('You cannot change or delete your own active admin account.');
    redirect('admin.php');
}

if ($action === 'role') {
    $role = $_POST['role'] ?? '';
    if (!in_array($role, ROLES, true)) {
        setError('Invalid role selected.');
        redirect('admin.php');
    }

    foreach ($users as $index => $user) {
        if ($user['id'] === $id) {
            $users[$index]['role'] = $role;
            saveUsers($users);
            setMessage('User role updated.');
            redirect('admin.php');
        }
    }
}

if ($action === 'reset') {
    $newPassword = $_POST['newPassword'] ?? '';
    if (!strongPassword($newPassword)) {
        setError('New password must be at least 8 characters and include uppercase, lowercase, number, and symbol.');
        redirect('admin.php');
    }

    foreach ($users as $index => $user) {
        if ($user['id'] === $id) {
            $users[$index]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            saveUsers($users);
            setMessage('Password reset successfully.');
            redirect('admin.php');
        }
    }
}

if ($action === 'delete') {
    $users = array_values(array_filter($users, fn($user) => $user['id'] !== $id));
    saveUsers($users);
    setMessage('User removed.');
    redirect('admin.php');
}

setError('User action failed.');
redirect('admin.php');
