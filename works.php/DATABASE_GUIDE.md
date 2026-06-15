# Complete Guide: Connecting PHP Website to MySQL Database

## Table of Contents
1. [Project Folder Structure](#project-folder-structure)
2. [Database Setup](#database-setup)
3. [PHP Database Connection](#php-database-connection)
4. [Secure Coding Practices](#secure-coding-practices)
5. [CRUD Operations](#crud-operations)
6. [Best Practices](#best-practices)

---

## Project Folder Structure

```
project_root/
├── config/
│   └── database.php          # Database connection & configuration
├── includes/
│   ├── header.php            # Common header
│   ├── footer.php            # Common footer
│   └── functions.php         # Helper functions
├── public/
│   ├── index.php             # Homepage/list page
│   ├── create.php            # Add new record form
│   ├── edit.php              # Edit record form
│   ├── delete.php            # Delete record
│   ├── view.php              # View single record
│   └── css/
│       └── style.css         # Stylesheet
├── .gitignore                # Ignore sensitive files
└── README.md                 # Project documentation
```

---

## Database Setup

### Step 1: Create Database

```sql
CREATE DATABASE IF NOT EXISTS company_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE company_db;
```

### Step 2: Create Table

```sql
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL,
    position VARCHAR(50) NOT NULL,
    salary DECIMAL(10, 2) NOT NULL,
    hire_date DATE NOT NULL,
    department VARCHAR(50) NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT INDEX idx_search (name, email, position)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### Step 3: Insert Sample Data

```sql
INSERT INTO employees (name, email, phone, position, salary, hire_date, department, status) VALUES
('John Smith', 'john@example.com', '555-0101', 'Manager', 50000, '2023-01-15', 'Sales', 'active'),
('Jane Doe', 'jane@example.com', '555-0102', 'Developer', 60000, '2023-02-20', 'IT', 'active'),
('Bob Johnson', 'bob@example.com', '555-0103', 'Designer', 45000, '2023-03-10', 'Design', 'active');
```

---

## PHP Database Connection

### Step 1: Database Configuration (config/database.php)

```php
<?php
/**
 * Database Configuration
 * Using PDO for secure database operations
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'company_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', 3306);

// PDO DSN (Data Source Name)
define('DB_DSN', 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4');

// PDO Options
define('PDO_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
]);

/**
 * Get Database Connection
 * 
 * @return PDO Database connection object
 * @throws PDOException on connection error
 */
function getDatabase() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, PDO_OPTIONS);
            // Enable exceptions for errors
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Log error (in production, don't expose database details)
            error_log('Database Connection Error: ' . $e->getMessage());
            die('Database connection failed. Please try again later.');
        }
    }
    
    return $pdo;
}

/**
 * Close Database Connection
 */
function closeDatabase() {
    $pdo = null;
}
?>
```

---

## Secure Coding Practices

### 1. **Prepared Statements** (Prevent SQL Injection)

```php
// ❌ UNSAFE - SQL Injection vulnerability
$name = $_POST['name'];
$query = "SELECT * FROM employees WHERE name = '" . $name . "'";

// ✅ SAFE - Using prepared statements
$name = $_POST['name'];
$pdo = getDatabase();
$stmt = $pdo->prepare("SELECT * FROM employees WHERE name = ?");
$stmt->execute([$name]);
$results = $stmt->fetchAll();
```

### 2. **Input Validation & Sanitization**

```php
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[0-9\-\(\)\s]+$/', $phone);
}

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}
```

### 3. **Password Hashing** (for user tables)

```php
// Storing password
$password = password_hash($_POST['password'], PASSWORD_BCRYPT);

// Verifying password
if (password_verify($_POST['password'], $hashed_password)) {
    // Password is correct
}
```

### 4. **CSRF Protection**

```php
// Generate CSRF token
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// In form
echo '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';

// Verify token
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}
```

---

## CRUD Operations

### CREATE - Insert Data (public/create.php)

```php
<?php
require_once '../config/database.php';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    session_start();
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
    
    // Get and validate inputs
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $position = sanitizeInput($_POST['position'] ?? '');
    $salary = floatval($_POST['salary'] ?? 0);
    $hire_date = $_POST['hire_date'] ?? '';
    $department = sanitizeInput($_POST['department'] ?? '');
    
    // Validate
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email) || !validateEmail($email)) $errors[] = 'Valid email is required';
    if (empty($phone) || !validatePhone($phone)) $errors[] = 'Valid phone is required';
    if (empty($position)) $errors[] = 'Position is required';
    if ($salary <= 0) $errors[] = 'Salary must be greater than 0';
    if (empty($hire_date)) $errors[] = 'Hire date is required';
    if (empty($department)) $errors[] = 'Department is required';
    
    // If no errors, insert data
    if (empty($errors)) {
        try {
            $pdo = getDatabase();
            $stmt = $pdo->prepare("
                INSERT INTO employees (name, email, phone, position, salary, hire_date, department)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$name, $email, $phone, $position, $salary, $hire_date, $department]);
            $success = true;
            
            // Clear form
            $_POST = [];
            
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

// Generate CSRF token
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Employee</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Add New Employee</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">Employee added successfully!</div>
            <a href="index.php" class="btn">Back to List</a>
        <?php else: ?>
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="form">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required value="<?php echo $_POST['name'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required value="<?php echo $_POST['email'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="text" id="phone" name="phone" required value="<?php echo $_POST['phone'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="position">Position:</label>
                    <input type="text" id="position" name="position" required value="<?php echo $_POST['position'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="salary">Salary:</label>
                    <input type="number" id="salary" name="salary" step="0.01" required value="<?php echo $_POST['salary'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="hire_date">Hire Date:</label>
                    <input type="date" id="hire_date" name="hire_date" required value="<?php echo $_POST['hire_date'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="department">Department:</label>
                    <input type="text" id="department" name="department" required value="<?php echo $_POST['department'] ?? ''; ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Add Employee</button>
                <a href="index.php" class="btn">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
```

### READ - Retrieve Data (public/index.php)

```php
<?php
require_once '../config/database.php';

$search = '';
$employees = [];
$total = 0;

// Handle search
if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET['search'])) {
    $search = sanitizeInput($_GET['search']);
    
    try {
        $pdo = getDatabase();
        
        // Using LIKE with prepared statement
        $stmt = $pdo->prepare("
            SELECT * FROM employees 
            WHERE name LIKE ? OR email LIKE ? OR position LIKE ?
            ORDER BY id DESC
        ");
        
        $search_term = '%' . $search . '%';
        $stmt->execute([$search_term, $search_term, $search_term]);
        $employees = $stmt->fetchAll();
        $total = count($employees);
        
    } catch (PDOException $e) {
        die('Query error: ' . $e->getMessage());
    }
} else {
    // Get all employees
    try {
        $pdo = getDatabase();
        $stmt = $pdo->query("SELECT * FROM employees ORDER BY id DESC");
        $employees = $stmt->fetchAll();
        $total = count($employees);
        
    } catch (PDOException $e) {
        die('Query error: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Employees</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Employees</h1>
        
        <div class="toolbar">
            <form method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search name, email, position..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn">Search</button>
                <?php if ($search): ?>
                    <a href="index.php" class="btn">Clear</a>
                <?php endif; ?>
            </form>
            
            <a href="create.php" class="btn btn-primary">Add Employee</a>
        </div>
        
        <p>Total: <strong><?php echo $total; ?></strong> employees</p>
        
        <?php if (!empty($employees)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($emp['name']); ?></td>
                            <td><?php echo htmlspecialchars($emp['email']); ?></td>
                            <td><?php echo htmlspecialchars($emp['position']); ?></td>
                            <td><?php echo htmlspecialchars($emp['department']); ?></td>
                            <td>₱<?php echo number_format($emp['salary'], 2); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $emp['status']; ?>">
                                    <?php echo ucfirst($emp['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="view.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm">View</a>
                                <a href="edit.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm">Edit</a>
                                <a href="delete.php?id=<?php echo $emp['id']; ?>" class="btn btn-sm btn-danger" 
                                   onclick="return confirm('Are you sure?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No employees found.</p>
        <?php endif; ?>
    </div>
</body>
</html>
```

### UPDATE - Edit Data (public/edit.php)

```php
<?php
require_once '../config/database.php';

$id = intval($_GET['id'] ?? 0);
$employee = null;
$errors = [];
$success = false;

// Get employee data
try {
    $pdo = getDatabase();
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
    
    if (!$employee) {
        die('Employee not found');
    }
} catch (PDOException $e) {
    die('Query error: ' . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
    
    $name = sanitizeInput($_POST['name'] ?? '');
    $email = sanitizeInput($_POST['email'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $position = sanitizeInput($_POST['position'] ?? '');
    $salary = floatval($_POST['salary'] ?? 0);
    $hire_date = $_POST['hire_date'] ?? '';
    $department = sanitizeInput($_POST['department'] ?? '');
    
    // Validate
    if (empty($name)) $errors[] = 'Name is required';
    if (empty($email) || !validateEmail($email)) $errors[] = 'Valid email is required';
    if (empty($phone) || !validatePhone($phone)) $errors[] = 'Valid phone is required';
    if (empty($position)) $errors[] = 'Position is required';
    if ($salary <= 0) $errors[] = 'Salary must be greater than 0';
    if (empty($hire_date)) $errors[] = 'Hire date is required';
    if (empty($department)) $errors[] = 'Department is required';
    
    // Update if no errors
    if (empty($errors)) {
        try {
            $pdo = getDatabase();
            $stmt = $pdo->prepare("
                UPDATE employees 
                SET name = ?, email = ?, phone = ?, position = ?, salary = ?, hire_date = ?, department = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$name, $email, $phone, $position, $salary, $hire_date, $department, $id]);
            $success = true;
            
            // Refresh employee data
            $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
            $stmt->execute([$id]);
            $employee = $stmt->fetch();
            
        } catch (PDOException $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
}

session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Employee</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Edit Employee</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success">Employee updated successfully!</div>
        <?php endif; ?>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($employee['name']); ?>">
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($employee['email']); ?>">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="text" id="phone" name="phone" required value="<?php echo htmlspecialchars($employee['phone']); ?>">
            </div>
            
            <div class="form-group">
                <label for="position">Position:</label>
                <input type="text" id="position" name="position" required value="<?php echo htmlspecialchars($employee['position']); ?>">
            </div>
            
            <div class="form-group">
                <label for="salary">Salary:</label>
                <input type="number" id="salary" name="salary" step="0.01" required value="<?php echo $employee['salary']; ?>">
            </div>
            
            <div class="form-group">
                <label for="hire_date">Hire Date:</label>
                <input type="date" id="hire_date" name="hire_date" required value="<?php echo $employee['hire_date']; ?>">
            </div>
            
            <div class="form-group">
                <label for="department">Department:</label>
                <input type="text" id="department" name="department" required value="<?php echo htmlspecialchars($employee['department']); ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Update Employee</button>
            <a href="index.php" class="btn">Cancel</a>
        </form>
    </div>
</body>
</html>
```

### DELETE - Remove Data (public/delete.php)

```php
<?php
require_once '../config/database.php';

$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    die('Invalid employee ID');
}

try {
    $pdo = getDatabase();
    
    // Verify employee exists
    $stmt = $pdo->prepare("SELECT id FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    
    if (!$stmt->fetch()) {
        die('Employee not found');
    }
    
    // Delete employee
    $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    
    // Redirect to list
    header('Location: index.php?message=Employee deleted successfully');
    exit;
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>
```

---

## Helper Functions (includes/functions.php)

```php
<?php
/**
 * Helper Functions for Database Operations
 */

function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function validatePhone($phone) {
    return preg_match('/^[0-9\-\(\)\s\+]+$/', $phone);
}

function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

function redirectTo($url) {
    header("Location: $url");
    exit;
}

function setFlashMessage($message, $type = 'success') {
    session_start();
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

function getFlashMessage() {
    session_start();
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        $type = $_SESSION['flash_type'] ?? 'info';
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return ['message' => $message, 'type' => $type];
    }
    return null;
}
?>
```

---

## Best Practices

### 1. **Always Use Prepared Statements**
```php
// ✅ Correct
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);

// ❌ Wrong
$query = "SELECT * FROM users WHERE email = '$email'";
```

### 2. **Validate Input on Both Client and Server**
```php
// HTML5 validation (client-side)
<input type="email" required>

// PHP validation (server-side - always do this)
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email';
}
```

### 3. **Use Transactions for Multiple Operations**
```php
try {
    $pdo->beginTransaction();
    
    // Multiple operations
    $pdo->prepare("INSERT INTO ...")->execute([...]);
    $pdo->prepare("UPDATE ...")->execute([...]);
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
```

### 4. **Set Proper Error Handling**
```php
set_error_handler(function($errno, $errstr) {
    error_log("Error: $errstr");
    // Don't expose details to users
    die('An error occurred. Please try again later.');
});
```

### 5. **Use Environment Variables**
```php
// .env file (never commit to git)
DB_HOST=localhost
DB_NAME=company_db
DB_USER=root
DB_PASS=secure_password

// PHP
require 'vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$db_host = $_ENV['DB_HOST'];
```

### 6. **Log Important Actions**
```php
function logAction($action, $user_id, $details = '') {
    $log_file = '../logs/actions.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[$timestamp] User: $user_id | Action: $action | Details: $details\n";
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
```

### 7. **Use PDO Options for Security**
```php
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,  // Important for security
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
];
```

---

## Common SQL Queries

### Pagination
```php
$page = intval($_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

$stmt = $pdo->prepare("SELECT * FROM employees LIMIT ? OFFSET ?");
$stmt->execute([$limit, $offset]);
```

### Aggregation
```php
$stmt = $pdo->query("
    SELECT department, COUNT(*) as count, AVG(salary) as avg_salary 
    FROM employees 
    GROUP BY department
");
```

### Join Tables
```php
$stmt = $pdo->prepare("
    SELECT e.*, d.name as department_name 
    FROM employees e 
    JOIN departments d ON e.department_id = d.id 
    WHERE e.id = ?
");
```

---

## Security Checklist

- ✅ Use prepared statements for all queries
- ✅ Validate all user input on server-side
- ✅ Sanitize output with htmlspecialchars()
- ✅ Use password_hash() for passwords
- ✅ Implement CSRF tokens
- ✅ Use HTTPS in production
- ✅ Keep database credentials in .env file
- ✅ Use proper file permissions (chmod)
- ✅ Log security events
- ✅ Keep software updated
- ✅ Use strong database passwords
- ✅ Implement rate limiting for forms
- ✅ Use Content Security Policy (CSP) headers

---

## Resources

- [PHP PDO Documentation](https://www.php.net/manual/en/class.pdo.php)
- [MySQL Documentation](https://dev.mysql.com/doc/)
- [OWASP Security Guidelines](https://owasp.org/)
- [PHP Security Manual](https://www.php.net/manual/en/security.php)

