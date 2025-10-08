<?php
if (!session_id()) session_start();
require_once __DIR__ . '/../../config/db.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $err = 'Enter a valid email';
    } else {
        $stmt = $pdo->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            unset($user['password']);
            $_SESSION['user'] = $user;
            $_SESSION['flash'] = 'Login successful';
            header('Location: ../../index.php');
            exit;
        } else {
            $err = 'Invalid credentials';
        }
    }
}

include __DIR__ . '/../templates/header.php';
?>
<h2>Login</h2>

<?php if (!empty($err)): ?>
  <div class="alert error"><?php echo htmlspecialchars($err); ?></div>
<?php endif; ?>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="alert success"><?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
<?php endif; ?>

<form method="post" action="login.php">
  <div class="form-row">
    <label>Email</label>
    <input type="email" name="email" required>
  </div>
  <div class="form-row">
    <label>Password</label>
    <input type="password" name="password" required>
  </div>
  <button type="submit" class="btn">Login</button>
</form>

<?php include __DIR__ . '/../templates/footer.php'; ?>
