<?php
require_once __DIR__ . '/auth.php';
requireRole(['Admin']);

$students = allStudents();
$users = allUsers();
$today = date('Y-m-d');
$todayCount = count(array_filter($students, fn($student) => studentRegistrationDate($student) === $today));
$courses = array_unique(array_map(fn($student) => $student['course'], $students));

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

$pageTitle = 'Admin Dashboard - XWZ SRS';
require __DIR__ . '/header.php';
?>
<section class="hero">
    <h2>Admin Dashboard</h2>
    <p>Manage student records, reports, and system users.</p>
</section>

<section class="stats">
    <div class="stat"><span>Total Students</span><strong><?php echo count($students); ?></strong></div>
    <div class="stat"><span>Registered Today</span><strong><?php echo $todayCount; ?></strong></div>
    <div class="stat"><span>Users</span><strong><?php echo count($users); ?></strong></div>
</section>

<section class="panel">
    <h2>Student Registration Reports</h2>
    <?php foreach (['Daily' => 'Y-m-d', 'Weekly' => 'o-\WW', 'Monthly' => 'Y-m'] as $label => $format): ?>
        <h3><?php echo e($label); ?></h3>
        <?php $counts = reportCounts($students, $format); ?>
        <?php if (!$counts): ?>
            <p class="empty">No records yet.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Period</th><th>Students</th></tr></thead>
                <tbody>
                <?php foreach ($counts as $period => $count): ?>
                    <tr><td><?php echo e((string) $period); ?></td><td><?php echo e((string) $count); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    <?php endforeach; ?>
</section>

<section class="panel">
    <h2>User Management</h2>
    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?php echo e($user['id']); ?></td>
                    <td><?php echo e($user['firstName'] . ' ' . $user['lastName']); ?></td>
                    <td><?php echo e($user['email']); ?></td>
                    <td>
                        <form method="post" action="user_action.php">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="role">
                            <input type="hidden" name="id" value="<?php echo e($user['id']); ?>">
                            <select name="role">
                                <?php foreach (ROLES as $role): ?>
                                    <option <?php echo $user['role'] === $role ? 'selected' : ''; ?>><?php echo e($role); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit">Save</button>
                        </form>
                    </td>
                    <td class="actions">
                        <form method="post" action="user_action.php">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="reset">
                            <input type="hidden" name="id" value="<?php echo e($user['id']); ?>">
                            <input name="newPassword" placeholder="New password">
                            <button type="submit">Reset</button>
                        </form>
                        <form method="post" action="user_action.php" onsubmit="return confirm('Delete this user?')">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?php echo e($user['id']); ?>">
                            <button class="danger" type="submit">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/footer.php'; ?>
