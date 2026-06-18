<?php
require_once __DIR__ . '/auth.php';
requireRole(['Admin', 'Registrar']);

$students = allStudents();
$query = strtolower(trim($_GET['q'] ?? ''));
$course = strtolower(trim($_GET['course'] ?? ''));
$editId = trim($_GET['edit'] ?? '');
$editing = null;

foreach ($students as $student) {
    if ($student['studentId'] === $editId) {
        $editing = $student;
    }
}

$filtered = array_values(array_filter($students, function ($student) use ($query, $course) {
    $searchText = strtolower($student['studentId'] . ' ' . $student['name'] . ' ' . $student['contact']);
    $matchesQuery = $query === '' || str_contains($searchText, $query);
    $matchesCourse = $course === '' || strtolower($student['course']) === $course;
    return $matchesQuery && $matchesCourse;
}));

$today = date('Y-m-d');
$todayCount = count(array_filter($students, fn($student) => studentRegistrationDate($student) === $today));
$courses = array_unique(array_map(fn($student) => $student['course'], $students));

$pageTitle = 'Registrar Dashboard - XWZ SRS';
require __DIR__ . '/header.php';
?>
<section class="hero">
    <h2>Registrar Dashboard</h2>
    <p>Register students, update records, search information, and prevent duplicate student IDs.</p>
</section>

<section class="stats">
    <div class="stat"><span>Total Students</span><strong><?php echo count($students); ?></strong></div>
    <div class="stat"><span>Registered Today</span><strong><?php echo $todayCount; ?></strong></div>
    <div class="stat"><span>Courses</span><strong><?php echo count($courses); ?></strong></div>
</section>

<section class="grid">
    <div class="panel">
        <h2><?php echo $editing ? 'Edit Student' : 'Register Student'; ?></h2>
        <form method="post" action="student_action.php">
            <?php echo csrfField(); ?>
            <input type="hidden" name="originalStudentId" value="<?php echo e($editing['studentId'] ?? ''); ?>">
            <label>Student ID<input name="studentId" value="<?php echo e($editing['studentId'] ?? ''); ?>" required></label>
            <label>Name<input name="name" value="<?php echo e($editing['name'] ?? ''); ?>" required></label>
            <label>Course<input name="course" value="<?php echo e($editing['course'] ?? ''); ?>" required></label>
            <label>Year<input name="year" value="<?php echo e($editing['year'] ?? ''); ?>" required></label>
            <label>Contact<input name="contact" value="<?php echo e($editing['contact'] ?? ''); ?>" required></label>
            <label>Registration Date<input type="date" name="registrationDate" value="<?php echo e($editing['registrationDate'] ?? date('Y-m-d')); ?>" required></label>
            <button type="submit"><?php echo $editing ? 'Update Student' : 'Save Student'; ?></button>
        </form>
    </div>

    <div class="panel wide">
        <div class="toolbar">
            <h2>Student Records</h2>
            <form class="filters" method="get" action="registrar.php">
                <input name="q" placeholder="Search ID, name, contact" value="<?php echo e($_GET['q'] ?? ''); ?>">
                <input name="course" placeholder="Filter course" value="<?php echo e($_GET['course'] ?? ''); ?>">
                <button type="submit">Search</button>
            </form>
        </div>

        <?php if (!$filtered): ?>
            <p class="empty">No student records found.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr><th>ID</th><th>Name</th><th>Course</th><th>Year</th><th>Contact</th><th>Registration Date</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($filtered as $student): ?>
                        <tr>
                            <td><?php echo e($student['studentId']); ?></td>
                            <td><?php echo e($student['name']); ?></td>
                            <td><?php echo e($student['course']); ?></td>
                            <td><?php echo e($student['year']); ?></td>
                            <td><?php echo e($student['contact']); ?></td>
                            <td><?php echo e(studentRegistrationDate($student)); ?></td>
                            <td class="actions">
                                <a href="registrar.php?edit=<?php echo e($student['studentId']); ?>">Edit</a>
                                <form method="post" action="delete_student.php" onsubmit="return confirm('Delete this student?')">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="studentId" value="<?php echo e($student['studentId']); ?>">
                                    <button class="danger" type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/footer.php'; ?>
