<?php
require_once __DIR__ . '/auth.php';
requireRole(['Student']);

$students = allStudents();
$pageTitle = 'Student Dashboard - XWZ SRS';
require __DIR__ . '/header.php';
?>
<section class="hero">
    <h2>Student Dashboard</h2>
    <p>Welcome to XWZ School Student Registration System.</p>
</section>
<section class="stats">
    <div class="stat"><span>Total Registered Students</span><strong><?php echo count($students); ?></strong></div>
    <div class="stat"><span>Your Role</span><strong>Student</strong></div>
    <div class="stat"><span>Access</span><strong>View</strong></div>
</section>
<section class="panel">
    <h3>Student Access</h3>
    <p>Student users can access their dashboard only. Student registration records are managed by Registrar and Admin users.</p>
</section>
<?php require __DIR__ . '/footer.php'; ?>
