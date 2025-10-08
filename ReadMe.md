# Student Club Event Registration System

## Overview
The **Student Club Event Registration System** is a simple PHP & MySQL web application that allows students to view upcoming club events, register for them, and manage their accounts. Administrators can manage users, events, and registrations through a secure dashboard.

This project was developed as part of a university web design and development assignment. It focuses on simplicity, functionality, and maintainable code structure.

---

## Features
- User registration and login
- Admin and regular user roles
- Event management (create, view, edit, delete)
- Event registration and automatic capacity tracking
- Admin dashboard with user and event statistics (Chart.js)
- Responsive, minimal front-end design
- JavaScript-based interactive animations
- Smoke test to verify database connectivity and structure
- Organized folder structure for clarity and scalability

---

## Technologies
| Category | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript |
| Backend | PHP 8+ |
| Database | MySQL (via phpMyAdmin or MySQL CLI) |
| Server | Apache (XAMPP environment) or PHP built-in server |
| Charts | Chart.js (admin dashboard visualization) |

---

## Folder Structure
```
student-club-event-system/
├── config/
│   └── db.php
├── public/
│   ├── index.php
│   ├── events.php
│   ├── register.php
│   └── login.php
├── src/
│   ├── admin/
│   │   ├── dashboard.php
│   │   ├── events.php
│   │   └── users.php
│   ├── auth/
│   │   ├── login.php
│   │   └── register.php
│   └── templates/
│       ├── header.php
│       └── footer.php
├── sql/
│   ├── schema.sql
│   └── seed.sql
├── static/
│   ├── css/
│   │   └── style.css
│   └── js/
│       └── script.js
├── tests/
│   └── smoke_test.php
├── uploads/
└── README.md
```

---

## Prerequisites
Make sure the following are installed:
- XAMPP (recommended) **or** PHP 8+ and MySQL
- MySQL 5.7+ (or compatible)
- A modern web browser (Chrome, Edge, Firefox)
- Git (optional, if cloning from a repository)

---

## Installation

### 1. Place project in web root
Place the project folder inside your XAMPP `htdocs` directory (example path for Windows):
```
C:/xampp/htdocs/student-club-event-system
```

If using Git:
```bash
git clone <<>repo link>>
```

### 2. Start services
Start Apache and MySQL from the XAMPP Control Panel (or start `httpd` and `mysqld` if using another environment).

### 3. Create the database

#### Using phpMyAdmin (browser)
1. Open: `http://localhost/phpmyadmin`
2. Click **New** and create a database named: `club_db`
3. Select the new database, then use **Import** to upload and run, in order:
   - `sql/schema.sql`
   - `sql/seed.sql`

#### Using MySQL CLI
Open a terminal and run:
```bash
mysql -u root -p
# inside mysql prompt:
CREATE DATABASE club_db;
USE club_db;
SOURCE /path/to/student-club-event-system/sql/schema.sql;
SOURCE /path/to/student-club-event-system/sql/seed.sql;
```

> Adjust paths and credentials as needed.

### 4. Configure database connection
Edit `config/db.php` to match your environment. Default XAMPP credentials usually work:

```php
<?php
$host = 'localhost';
$dbname = 'club_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
```

---

## Running the Application

### Option A — Using XAMPP (recommended)
1. Ensure Apache and MySQL are running in XAMPP Control Panel.
2. Open browser:
```
http://localhost/student-club-event-system/public/
```

### Option B — PHP built-in server (for development)
Open a terminal and run:
```bash
cd C:/xampp/htdocs/student-club-event-system/public
php -S localhost:8000
```
Then open:
```
http://localhost:8000
```

---

## Default Admin Account
Use the seeded admin account to access admin features:

| Role  | Email                        | Password    |
|-------|------------------------------|-------------|
| Admin | admin@club.sydney.edu.au     | Admin@123   |

Use this account to:
- Add and manage events
- View total users, events, and registrations
- Manage user roles

> Change the password or create a new admin account after the first login for security.

---

## Running Tests — Smoke Test
A smoke test is included to verify database connectivity and essential tables.

#### Using full PHP path (Windows XAMPP example)
```powershell
& 'C:/xampp/php/php.exe' '.	ests\smoke_test.php'
```

#### If PHP is in your system PATH:
```bash
php tests/smoke_test.php
```

#### Expected output
```
PASS: Database connection established
PASS: Table exists: users
PASS: Table exists: events
PASS: Table exists: registrations
PASS: Users count: 3
PASS: Events count: 3
PASS: Admin accounts present: 1
PASS: All smoke tests passed
```

If any `FAIL` messages appear, recheck:
- `config/db.php` credentials
- Database import order (`schema.sql` then `seed.sql`)
- Folder structure and file permissions

---

## How to Use

### Regular Users
1. Visit the homepage and browse upcoming events.
2. Click **Register** to create an account.
3. Login using your email and password.
4. Click on an event to register.
5. View registered events in your profile.

### Admins
1. Login with admin credentials.
2. Navigate to **Dashboard** to see statistics and charts.
3. Add, edit, or remove events.
4. Manage user roles and view registrations.

---

## Testing the Setup (Manual Checklist)
- Load the homepage — events should appear.
- Register a test user (new account).
- Register that user for an event.
- Login as admin and verify the registration appears on the dashboard.
- Run the smoke test.

---

## Troubleshooting

| Issue | Possible Cause | Solution |
|---:|---|---|
| “Database connection failed” | Incorrect credentials in `config/db.php` | Update `username` / `password` / `dbname` to match your MySQL setup |
| Blank page / white screen | PHP errors suppressed | Enable `display_errors` in `php.ini` or check Apache/PHP error logs |
| Missing CSS/JS | Incorrect relative paths or wrong folder placement | Confirm `static/` folder is present and paths in templates are correct |
| Access denied for admin pages | Not logged in or wrong role | Login as admin or check session/auth logic |
| Uploads failing | Folder permissions | Ensure `uploads/` is writable by the web server user |

---

## Security Notes
- Do not use default admin credentials in production.
- Sanitize and validate all user inputs (server-side).
- Use HTTPS in production environments.
- Implement CSRF protection for forms.
- Hash passwords using a secure algorithm such as `password_hash()` (bcrypt).

---

## Extending the Project (Ideas)
- Add email confirmation for new registrants.
- Add role-based permissions (editor, moderator).
- Implement pagination for events and users.
- Add export (CSV) of registrations.
- Integrate OAuth (Google/University SSO) for login.
- Add unit and integration tests.

---

## Contributing
1. Fork the repository.
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Commit changes: `git commit -m "Add feature"`
4. Push branch and open a pull request.

Please keep changes focused, document DB/structure changes, and include tests when possible.

