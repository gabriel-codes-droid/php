<?php
require_once __DIR__ . '/auth.php';
loggedInRedirectAwayFromAuthPages();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $users = allUsers();
    $newUser = [
        'id' => trim($_POST['id'] ?? ''),
        'firstName' => trim($_POST['firstName'] ?? ''),
        'lastName' => trim($_POST['lastName'] ?? ''),
        'email' => strtolower(trim($_POST['email'] ?? '')),
        'password' => $_POST['password'] ?? '',
        'role' => 'Student',
        'createdAt' => date('Y-m-d H:i:s')
    ];

    if ($newUser['id'] === '' || $newUser['firstName'] === '' || $newUser['lastName'] === '' || $newUser['email'] === '' || $newUser['password'] === '') {
        setError('All signup fields are required.');
        redirect('signup.php');
    }

    if (!filter_var($newUser['email'], FILTER_VALIDATE_EMAIL)) {
        setError('Enter a valid email address.');
        redirect('signup.php');
    }

    if (!strongPassword($newUser['password'])) {
        setError('Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.');
        redirect('signup.php');
    }

    foreach ($users as $user) {
        if ($user['id'] === $newUser['id']) {
            setError('A user with that ID already exists.');
            redirect('signup.php');
        }
        if ($user['email'] === $newUser['email']) {
            setError('A user with that email already exists.');
            redirect('signup.php');
        }
    }

    $newUser['password'] = password_hash($newUser['password'], PASSWORD_DEFAULT);
    $users[] = $newUser;
    saveUsers($users);
    setMessage('Account created successfully. You can now log in.');
    redirect('login.php');
}

$pageTitle = 'Signup - XWZ SRS';
require __DIR__ . '/header.php';
?>
<section class="panel auth">
    <h2>User Signup</h2>
    <form method="post" action="signup.php">
        <?php echo csrfField(); ?>
        <label>ID<input name="id" required></label>
        <label>First name<input name="firstName" required></label>
        <label>Last name<input name="lastName" required></label>
        <label>Email<input type="email" name="email" required></label>
        <label>Password<input type="password" name="password" required></label>
        <button type="submit">Create Account</button>
    </form>
    <p class="hint">New accounts start as Student. Admin can change roles.</p>
</section>
<?php require __DIR__ . '/footer.php'; ?>
