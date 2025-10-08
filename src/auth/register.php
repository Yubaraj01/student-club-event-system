<?php
if (!session_id()) session_start();
require_once __DIR__ . '/../../config/db.php';

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if ($name === '') $errors[] = 'Name is required';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';
    if ($password !== $confirm) $errors[] = 'Passwords do not match';

    if (!$errors) {
        // Check duplicate email
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already registered';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            // Default role: user. To create admin use seed sql or update DB.
            $insert = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            $insert->execute([$name, $email, $hash, 'user']);
            $_SESSION['flash'] = 'Registration successful. Please login.';
            header('Location: login.php');
            exit;
        }
    }
}

include __DIR__ . '/../templates/header.php';
?>
<h2>Register</h2>

<?php if ($errors): ?>
  <div class="alert error">
    <?php foreach ($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?>
  </div>
<?php endif; ?>

<form method="post" action="register.php">
  <div class="form-row">
    <label>Name</label>
    <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
  </div>
  <div class="form-row">
    <label>Email</label>
    <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
  </div>
  <div class="form-row">
    <label>Password</label>
    <input type="password" name="password" required>
  </div>
  <div class="form-row">
    <label>Confirm password</label>
    <input type="password" name="confirm" required>
  </div>
  <button type="submit" class="btn">Register</button>
</form>

<?php include __DIR__ . '/../templates/footer.php'; ?>
