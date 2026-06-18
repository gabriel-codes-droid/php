<?php
declare(strict_types=1);

const USERS_FILE = __DIR__ . '/data/users.json';
const STUDENTS_FILE = __DIR__ . '/data/students.json';
const ROLES = ['Admin', 'Registrar', 'Student'];

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

function allUsers(): array
{
    return loadJson(USERS_FILE);
}

function saveUsers(array $users): void
{
    saveJson(USERS_FILE, $users);
}

function allStudents(): array
{
    return loadJson(STUDENTS_FILE);
}

function saveStudents(array $students): void
{
    saveJson(STUDENTS_FILE, $students);
}
