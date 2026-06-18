XWZ School Student Registration System
======================================

Technology:
- PHP
- HTML/CSS
- JSON file storage

Main Features:
--------------
1. User Management
   - Roles: Admin, Registrar, Student
   - User registration and login
   - Secure password hashing with password_hash()
   - Login/logout session management
   - Role-based dashboards and access control
   - Admin user management: change roles, reset passwords, remove users

2. Student Registration
   - Register student records
   - Fields: studentId, name, course, year, contact, registrationDate
   - Create, Read, Update, Delete student records
   - Search by student ID, name, or contact
   - Filter by course
   - Duplicate student IDs are prevented
   - Registration date is required in YYYY-MM-DD format

3. Admin Dashboard and Reports
   - Overview of total students
   - Number of students registered today
   - Daily, weekly, and monthly registration reports

4. Security and Validation
   - Strong password validation
   - Unique email validation
   - Logged-in users cannot return to login or signup pages
   - Logged-out users cannot access dashboard pages
   - CSRF token protection
   - Secure PHP sessions
   - Browser cache protection after logout
   - Error and success messages

Default Admin Login:
--------------------
Email: admin@xwzschool.rw
Password: Admin@123

How to Run with XAMPP:
----------------------
1. Copy the whole srs folder into:
   C:\xampp\htdocs\

2. Start Apache from XAMPP Control Panel.

3. Open your browser and visit:
   http://localhost/srs/

How to Run with PHP Built-in Server:
------------------------------------
1. Open a terminal in the srs folder.

2. Run:
   php -S localhost:8000

3. Open your browser and visit:
   http://localhost:8000

Data Files:
-----------
The system stores data in:
- data/users.json
- data/students.json

These files are created automatically when the system starts.
