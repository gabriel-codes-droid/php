<?php
require_once __DIR__ . '/auth.php';
requireRole(['Admin', 'Registrar']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('registrar.php');
}

verifyCsrf();

$studentId = trim($_POST['studentId'] ?? '');
$students = array_values(array_filter(allStudents(), fn($student) => $student['studentId'] !== $studentId));
saveStudents($students);
setMessage('Student record deleted.');
redirect('registrar.php');
