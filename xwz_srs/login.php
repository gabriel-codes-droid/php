<?php
require_once __DIR__ . '/auth.php';
loggedInRedirectAwayFromAuthPages();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    foreach (allUsers() as $user) {
        if ($user['email'] === $email && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'firstName' => $user['firstName'],
                'lastName' => $user['lastName'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
            redirect('dashboard.php');
        }
    }

    setError('Invalid email or password.');
    redirect('login.php');
}

$pageTitle = 'Login - XWZ SRS';
require __DIR__ . '/header.php';
?>
<section class="panel auth">
    <h2>Login</h2>
    <p>Use your school account to continue.</p>
    <form method="post" action="login.php">
        <?php echo csrfField(); ?>
        <label>Email<input type="email" name="email" required></label>
        <label>Password<input type="password" name="password" required></label>
        <button type="submit">Login</button>
    </form>
    <p class="hint">Default admin: admin@xwzschool.rw / Admin@123</p>
</section>
<?php require __DIR__ . '/footer.php'; ?>
