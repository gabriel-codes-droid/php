<?php
require_once __DIR__ . '/auth.php';
requireRole(['Admin', 'Registrar']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('registrar.php');
}

verifyCsrf();

$students = allStudents();
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
    setError('All student fields are required.');
    redirect('registrar.php');
}

if (!validDate($student['registrationDate'])) {
    setError('Registration date must be entered in YYYY-MM-DD format.');
    redirect('registrar.php');
}

foreach ($students as $existing) {
    if ($existing['studentId'] === $student['studentId'] && $existing['studentId'] !== $originalId) {
        setError('A student with that student ID already exists.');
        redirect('registrar.php');
    }
}

if ($originalId !== '') {
    foreach ($students as $index => $existing) {
        if ($existing['studentId'] === $originalId) {
            $student['registeredAt'] = $existing['registeredAt'];
            $student['registeredBy'] = $existing['registeredBy'];
            $student['updatedAt'] = date('Y-m-d H:i:s');
            $students[$index] = $student;
            saveStudents($students);
            setMessage('Student record updated successfully.');
            redirect('registrar.php');
        }
    }
    setError('Student record was not found.');
    redirect('registrar.php');
}

$students[] = $student;
saveStudents($students);
setMessage('Student registered successfully.');
redirect('registrar.php');
