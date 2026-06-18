<?php
declare(strict_types=1);

session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Strict'
]);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

const USERS_FILE = __DIR__ . '/data/users.json';
const STUDENTS_FILE = __DIR__ . '/data/students.json';
const ROLES = ['Admin', 'Registrar', 'Student'];

initializeStorage();

$action = $_GET['action'] ?? 'dashboard';
$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    handlePost($action);
}

renderPage($action, $message, $error);

function initializeStorage(): void
{
    if (!is_dir(__DIR__ . '/data')) {
        mkdir(__DIR__ . '/data', 0777, true);
    }

    if (!file_exists(USERS_FILE)) {
        $admin = [
            'id' => 'U001',
            'firstName' => 'System',
            'lastName' => 'Admin',
            'email' => 'admin@xwzschool.rw',
            'password' => password_hash('Admin@123', PASSWORD_DEFAULT),
            'role' => 'Admin',
            'createdAt' => date('Y-m-d H:i:s')
        ];
        saveJson(USERS_FILE, [$admin]);
    }

    if (!file_exists(STUDENTS_FILE)) {
        saveJson(STUDENTS_FILE, []);
    }
}

function loadJson(string $file): array
{
    $content = file_get_contents($file);
    $data = json_decode($content ?: '[]', true);
    return is_array($data) ? $data : [];
}

function saveJson(string $file, array $data): void
{
    file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

function verifyCsrf(): void
{
    if (!isset($_POST['csrf'], $_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        $_SESSION['error'] = 'Security check failed. Please try again.';
        redirect('login');
    }
}

function handlePost(string $action): void
{
    if ($action === 'register_user') {
        registerUser();
    } elseif ($action === 'login') {
        loginUser();
    } elseif ($action === 'logout') {
        logoutUser();
    } elseif ($action === 'save_student') {
        requireRole(['Admin', 'Registrar']);
        saveStudent();
    } elseif ($action === 'delete_student') {
        requireRole(['Admin', 'Registrar']);
        deleteStudent();
    } elseif ($action === 'update_user_role') {
        requireRole(['Admin']);
        updateUserRole();
    } elseif ($action === 'delete_user') {
        requireRole(['Admin']);
        deleteUser();
    } elseif ($action === 'reset_password') {
        requireRole(['Admin']);
        resetPassword();
    }
}

function registerUser(): void
{
    if (currentUser()) {
        fail('You are already logged in. Logout before creating another account.', 'dashboard');
    }

    $users = loadJson(USERS_FILE);
    $user = [
        'id' => trim($_POST['id'] ?? ''),
        'firstName' => trim($_POST['firstName'] ?? ''),
        'lastName' => trim($_POST['lastName'] ?? ''),
        'email' => strtolower(trim($_POST['email'] ?? '')),
        'password' => $_POST['password'] ?? '',
        'role' => 'Student',
        'createdAt' => date('Y-m-d H:i:s')
    ];

    if ($user['id'] === '' || $user['firstName'] === '' || $user['lastName'] === '' || $user['email'] === '' || $user['password'] === '') {
        fail('All user registration fields are required.', 'register');
    }

    if (!filter_var($user['email'], FILTER_VALIDATE_EMAIL)) {
        fail('Enter a valid email address.', 'register');
    }

    if (!strongPassword($user['password'])) {
        fail('Password must be at least 8 characters and include uppercase, lowercase, number, and symbol.', 'register');
    }

    foreach ($users as $existing) {
        if ($existing['id'] === $user['id']) {
            fail('A user with that ID already exists.', 'register');
        }
        if ($existing['email'] === $user['email']) {
            fail('A user with that email already exists.', 'register');
        }
    }

    $user['password'] = password_hash($user['password'], PASSWORD_DEFAULT);
    $users[] = $user;
    saveJson(USERS_FILE, $users);
    $_SESSION['message'] = 'Account created successfully. You can now log in.';
    redirect('login');
}

function loginUser(): void
{
    if (currentUser()) {
        redirect('dashboard');
    }

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $users = loadJson(USERS_FILE);

    foreach ($users as $user) {
        if ($user['email'] === $email && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => $user['id'],
                'firstName' => $user['firstName'],
                'lastName' => $user['lastName'],
                'email' => $user['email'],
                'role' => $user['role']
            ];
            redirect('dashboard');
        }
    }

    fail('Invalid email or password.', 'login');
}

function logoutUser(): void
{
    $_SESSION = [];
    session_destroy();
    header('Location: index.php?action=login');
    exit;
}

function saveStudent(): void
{
    $students = loadJson(STUDENTS_FILE);
    $originalId = trim($_POST['originalStudentId'] ?? '');
    $student = [
        'studentId' => trim($_POST['studentId'] ?? ''),
        'name' => trim($_POST['name'] ?? ''),
        'course' => trim($_POST['course'] ?? ''),
        'year' => trim($_POST['year'] ?? ''),
        'contact' => trim($_POST['contact'] ?? ''),
        'registrationDate' => trim($_POST['registrationDate'] ?? ''),
        'registeredAt' => date('Y-m-d H:i:s'),
        'registeredBy' => currentUser()['email']
    ];

    if ($student['studentId'] === '' || $student['name'] === '' || $student['course'] === '' || $student['year'] === '' || $student['contact'] === '' || $student['registrationDate'] === '') {
        fail('All student fields are required.', 'students');
    }

    if (!validDate($student['registrationDate'])) {
        fail('Registration date must be entered in YYYY-MM-DD format.', 'students');
    }

    foreach ($students as $existing) {
        if ($existing['studentId'] === $student['studentId'] && $existing['studentId'] !== $originalId) {
            fail('A student with that student ID already exists.', 'students');
        }
    }

    if ($originalId !== '') {
        foreach ($students as $index => $existing) {
            if ($existing['studentId'] === $originalId) {
                $student['registeredAt'] = $existing['registeredAt'];
                $student['registeredBy'] = $existing['registeredBy'];
                $student['updatedAt'] = date('Y-m-d H:i:s');
                $students[$index] = $student;
                saveJson(STUDENTS_FILE, $students);
                $_SESSION['message'] = 'Student record updated successfully.';
                redirect('students');
            }
        }
        fail('Student record was not found.', 'students');
    }

    $students[] = $student;
    saveJson(STUDENTS_FILE, $students);
    $_SESSION['message'] = 'Student registered successfully.';
    redirect('students');
}

function deleteStudent(): void
{
    $studentId = trim($_POST['studentId'] ?? '');
    $students = array_values(array_filter(loadJson(STUDENTS_FILE), fn($student) => $student['studentId'] !== $studentId));
    saveJson(STUDENTS_FILE, $students);
    $_SESSION['message'] = 'Student record deleted.';
    redirect('students');
}

function updateUserRole(): void
{
    $id = trim($_POST['id'] ?? '');
    $role = $_POST['role'] ?? '';

    if ($id === currentUser()['id']) {
        fail('You cannot change your own role while logged in.', 'users');
    }

    if (!in_array($role, ROLES, true)) {
        fail('Invalid role selected.', 'users');
    }

    $users = loadJson(USERS_FILE);
    foreach ($users as $index => $user) {
        if ($user['id'] === $id) {
            $users[$index]['role'] = $role;
            saveJson(USERS_FILE, $users);
            $_SESSION['message'] = 'User role updated.';
            redirect('users');
        }
    }

    fail('User was not found.', 'users');
}

function deleteUser(): void
{
    $id = trim($_POST['id'] ?? '');
    if ($id === currentUser()['id']) {
        fail('You cannot delete your own account while logged in.', 'users');
    }
    $users = array_values(array_filter(loadJson(USERS_FILE), fn($user) => $user['id'] !== $id));
    saveJson(USERS_FILE, $users);
    $_SESSION['message'] = 'User removed.';
    redirect('users');
}

function resetPassword(): void
{
    $id = trim($_POST['id'] ?? '');
    $newPassword = $_POST['newPassword'] ?? '';

    if (!strongPassword($newPassword)) {
        fail('New password must be strong.', 'users');
    }

    $users = loadJson(USERS_FILE);
    foreach ($users as $index => $user) {
        if ($user['id'] === $id) {
            $users[$index]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
            saveJson(USERS_FILE, $users);
            $_SESSION['message'] = 'Password reset successfully.';
            redirect('users');
        }
    }

    fail('User was not found.', 'users');
}

function strongPassword(string $password): bool
{
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[a-z]/', $password)
        && preg_match('/[0-9]/', $password)
        && preg_match('/[^A-Za-z0-9]/', $password);
}

function validDate(string $date): bool
{
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed && $parsed->format('Y-m-d') === $date;
}

function studentRegistrationDate(array $student): string
{
    if (!empty($student['registrationDate'])) {
        return $student['registrationDate'];
    }

    return substr($student['registeredAt'] ?? date('Y-m-d'), 0, 10);
}

function currentUser(): ?array
{
    return $_SESSION['user'] ?? null;
}

function requireLogin(): void
{
    if (!currentUser()) {
        redirect('login');
    }
}

function requireRole(array $roles): void
{
    requireLogin();
    if (!in_array(currentUser()['role'], $roles, true)) {
        fail('You do not have permission to access that page.', 'dashboard');
    }
}

function redirect(string $action): void
{
    header('Location: index.php?action=' . urlencode($action));
    exit;
}

function fail(string $message, string $action): void
{
    $_SESSION['error'] = $message;
    redirect($action);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function renderPage(string $action, string $message, string $error): void
{
    $publicActions = ['login', 'register'];
    if (currentUser() && in_array($action, $publicActions, true)) {
        redirect('dashboard');
    }

    if (!in_array($action, $publicActions, true)) {
        requireLogin();
    }

    $title = 'XWZ School SRS';
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0">';
    echo '<title>' . e($title) . '</title><link rel="stylesheet" href="style.css"></head><body>';
    echo '<div class="app">';
    renderSidebar($action);
    echo '<main class="main">';
    if ($message) echo '<div class="alert success">' . e($message) . '</div>';
    if ($error) echo '<div class="alert error">' . e($error) . '</div>';

    if ($action === 'login') renderLogin();
    elseif ($action === 'register') renderRegister();
    elseif ($action === 'students') renderStudents();
    elseif ($action === 'reports') renderReports();
    elseif ($action === 'users') renderUsers();
    else renderDashboard();

    echo '</main></div></body></html>';
}

function renderSidebar(string $action): void
{
    $user = currentUser();
    echo '<aside class="sidebar">';
    echo '<h1>XWZ SRS</h1>';
    echo '<p>Student Registration System</p>';

    if ($user) {
        echo '<div class="profile"><strong>' . e($user['firstName'] . ' ' . $user['lastName']) . '</strong><span>' . e($user['role']) . '</span></div>';
        navLink('dashboard', 'Dashboard', $action);
        if (in_array($user['role'], ['Admin', 'Registrar'], true)) navLink('students', 'Students', $action);
        if ($user['role'] === 'Admin') {
            navLink('reports', 'Reports', $action);
            navLink('users', 'Users', $action);
        }
        echo '<form method="post" action="index.php?action=logout">' . csrfField() . '<button class="ghost" type="submit">Logout</button></form>';
    } else {
        navLink('login', 'Login', $action);
        navLink('register', 'Register', $action);
    }

    echo '</aside>';
}

function navLink(string $target, string $label, string $action): void
{
    $class = $action === $target ? 'active' : '';
    echo '<a class="' . $class . '" href="index.php?action=' . e($target) . '">' . e($label) . '</a>';
}

function renderLogin(): void
{
    echo '<section class="panel auth"><h2>Login</h2><p>Use your school account to continue.</p>';
    echo '<form method="post" action="index.php?action=login">' . csrfField();
    echo '<label>Email<input type="email" name="email" required></label>';
    echo '<label>Password<input type="password" name="password" required></label>';
    echo '<button type="submit">Login</button></form>';
    echo '<p class="hint">Default admin: admin@xwzschool.rw / Admin@123</p></section>';
}

function renderRegister(): void
{
    echo '<section class="panel auth"><h2>User Registration</h2>';
    echo '<form method="post" action="index.php?action=register_user">' . csrfField();
    echo '<label>ID<input name="id" required></label>';
    echo '<label>First name<input name="firstName" required></label>';
    echo '<label>Last name<input name="lastName" required></label>';
    echo '<label>Email<input type="email" name="email" required></label>';
    echo '<label>Password<input type="password" name="password" required></label>';
    echo '<button type="submit">Create Account</button></form>';
    echo '<p class="hint">New accounts start as Student. Admin can change roles.</p></section>';
}

function renderDashboard(): void
{
    $user = currentUser();
    $students = loadJson(STUDENTS_FILE);
    $today = date('Y-m-d');
    $todayCount = count(array_filter($students, fn($student) => studentRegistrationDate($student) === $today));
    $courses = array_unique(array_map(fn($student) => $student['course'], $students));

    echo '<section class="hero"><h2>' . e($user['role']) . ' Dashboard</h2><p>Welcome to XWZ School digital student registration.</p></section>';
    echo '<section class="stats">';
    statCard('Total Students', (string) count($students));
    statCard('Registered Today', (string) $todayCount);
    statCard('Courses', (string) count($courses));
    echo '</section>';

    if ($user['role'] === 'Student') {
        echo '<section class="panel"><h3>Student Access</h3><p>You can view your dashboard after logging in. Registration records are managed by Registrar and Admin users.</p></section>';
    } else {
        echo '<section class="panel"><h3>Recent Students</h3>';
        renderStudentTable(array_slice(array_reverse($students), 0, 5), false);
        echo '</section>';
    }
}

function statCard(string $label, string $value): void
{
    echo '<div class="stat"><span>' . e($label) . '</span><strong>' . e($value) . '</strong></div>';
}

function renderStudents(): void
{
    requireRole(['Admin', 'Registrar']);
    $students = loadJson(STUDENTS_FILE);
    $query = strtolower(trim($_GET['q'] ?? ''));
    $course = strtolower(trim($_GET['course'] ?? ''));
    $editId = trim($_GET['edit'] ?? '');
    $editing = null;

    foreach ($students as $student) {
        if ($student['studentId'] === $editId) $editing = $student;
    }

    $filtered = array_filter($students, function ($student) use ($query, $course) {
        $matchesQuery = $query === '' || str_contains(strtolower($student['studentId'] . ' ' . $student['name'] . ' ' . $student['contact']), $query);
        $matchesCourse = $course === '' || strtolower($student['course']) === $course;
        return $matchesQuery && $matchesCourse;
    });

    echo '<section class="grid">';
    echo '<div class="panel"><h2>' . ($editing ? 'Edit Student' : 'Register Student') . '</h2>';
    echo '<form method="post" action="index.php?action=save_student">' . csrfField();
    echo '<input type="hidden" name="originalStudentId" value="' . e($editing['studentId'] ?? '') . '">';
    echo '<label>Student ID<input name="studentId" value="' . e($editing['studentId'] ?? '') . '" required></label>';
    echo '<label>Name<input name="name" value="' . e($editing['name'] ?? '') . '" required></label>';
    echo '<label>Course<input name="course" value="' . e($editing['course'] ?? '') . '" required></label>';
    echo '<label>Year<input name="year" value="' . e($editing['year'] ?? '') . '" required></label>';
    echo '<label>Contact<input name="contact" value="' . e($editing['contact'] ?? '') . '" required></label>';
    echo '<label>Registration Date<input type="date" name="registrationDate" value="' . e($editing['registrationDate'] ?? date('Y-m-d')) . '" required></label>';
    echo '<button type="submit">' . ($editing ? 'Update Student' : 'Save Student') . '</button></form></div>';

    echo '<div class="panel wide"><div class="toolbar"><h2>Student Records</h2>';
    echo '<form class="filters" method="get"><input type="hidden" name="action" value="students">';
    echo '<input name="q" placeholder="Search ID, name, contact" value="' . e($_GET['q'] ?? '') . '">';
    echo '<input name="course" placeholder="Filter course" value="' . e($_GET['course'] ?? '') . '">';
    echo '<button type="submit">Search</button></form></div>';
    renderStudentTable(array_values($filtered), true);
    echo '</div></section>';
}

function renderStudentTable(array $students, bool $actions): void
{
    if (!$students) {
        echo '<p class="empty">No student records found.</p>';
        return;
    }

    echo '<div class="table-wrap"><table><thead><tr><th>ID</th><th>Name</th><th>Course</th><th>Year</th><th>Contact</th><th>Registration Date</th>';
    if ($actions) echo '<th>Actions</th>';
    echo '</tr></thead><tbody>';
    foreach ($students as $student) {
        echo '<tr><td>' . e($student['studentId']) . '</td><td>' . e($student['name']) . '</td><td>' . e($student['course']) . '</td><td>' . e($student['year']) . '</td><td>' . e($student['contact']) . '</td><td>' . e(studentRegistrationDate($student)) . '</td>';
        if ($actions) {
            echo '<td class="actions"><a href="index.php?action=students&edit=' . e($student['studentId']) . '">Edit</a>';
            echo '<form method="post" action="index.php?action=delete_student" onsubmit="return confirm(\'Delete this student?\')">' . csrfField();
            echo '<input type="hidden" name="studentId" value="' . e($student['studentId']) . '"><button class="danger" type="submit">Delete</button></form></td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

function renderReports(): void
{
    requireRole(['Admin']);
    $students = loadJson(STUDENTS_FILE);
    echo '<section class="panel"><h2>Registration Reports</h2>';
    reportBlock('Daily', reportCounts($students, 'Y-m-d'));
    reportBlock('Weekly', reportCounts($students, 'o-\WW'));
    reportBlock('Monthly', reportCounts($students, 'Y-m'));
    echo '</section>';
}

function reportCounts(array $students, string $format): array
{
    $counts = [];
    foreach ($students as $student) {
        $key = date($format, strtotime(studentRegistrationDate($student)));
        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }
    krsort($counts);
    return $counts;
}

function reportBlock(string $title, array $counts): void
{
    echo '<h3>' . e($title) . '</h3>';
    if (!$counts) {
        echo '<p class="empty">No records yet.</p>';
        return;
    }
    echo '<table><thead><tr><th>Period</th><th>Students</th></tr></thead><tbody>';
    foreach ($counts as $period => $count) {
        echo '<tr><td>' . e((string) $period) . '</td><td>' . e((string) $count) . '</td></tr>';
    }
    echo '</tbody></table>';
}

function renderUsers(): void
{
    requireRole(['Admin']);
    $users = loadJson(USERS_FILE);
    echo '<section class="panel"><h2>User Management</h2><div class="table-wrap"><table><thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead><tbody>';
    foreach ($users as $user) {
        echo '<tr><td>' . e($user['id']) . '</td><td>' . e($user['firstName'] . ' ' . $user['lastName']) . '</td><td>' . e($user['email']) . '</td><td>';
        echo '<form method="post" action="index.php?action=update_user_role">' . csrfField() . '<input type="hidden" name="id" value="' . e($user['id']) . '"><select name="role">';
        foreach (ROLES as $role) {
            $selected = $user['role'] === $role ? 'selected' : '';
            echo '<option ' . $selected . '>' . e($role) . '</option>';
        }
        echo '</select><button type="submit">Save</button></form></td><td class="actions">';
        echo '<form method="post" action="index.php?action=reset_password">' . csrfField() . '<input type="hidden" name="id" value="' . e($user['id']) . '"><input name="newPassword" placeholder="New password"><button type="submit">Reset</button></form>';
        echo '<form method="post" action="index.php?action=delete_user" onsubmit="return confirm(\'Delete this user?\')">' . csrfField() . '<input type="hidden" name="id" value="' . e($user['id']) . '"><button class="danger" type="submit">Remove</button></form>';
        echo '</td></tr>';
    }
    echo '</tbody></table></div></section>';
}
