<?php

if (!session_id()) session_start();
require_once __DIR__ . '/../../config/db.php';

// Admin guard
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_token'];

// Ensure 'is_blocked' column exists (safe, run-once). If not possible (permission), ignore gracefully.
try {
    $colCheck = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_blocked'");
    $colCheck->execute();
    $exists = (int)$colCheck->fetchColumn();
    if (!$exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_blocked TINYINT(1) NOT NULL DEFAULT 0");
    }
} catch (Exception $e) {
    
}

function normalize_role($r) {
    $r = trim(strtolower($r));
    return ($r === 'admin') ? 'admin' : 'user';
}

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Basic CSRF check
    $token = $_POST['csrf'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $token)) {
        $_SESSION['flash'] = 'Invalid request (CSRF).';
        header('Location: users.php');
        exit;
    }

    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = normalize_role($_POST['role'] ?? 'user');
        $errors = [];

        if ($name === '') $errors[] = 'Name is required';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters';

        if (empty($errors)) {
            // Check duplicate email
            $s = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $s->execute([$email]);
            if ($s->fetch()) {
                $errors[] = 'Email already exists';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $pdo->prepare('INSERT INTO users (name, email, password, role, is_blocked) VALUES (?, ?, ?, ?, 0)');
                $ins->execute([$name, $email, $hash, $role]);
                $_SESSION['flash'] = 'User added successfully.';
                header('Location: users.php');
                exit;
            }
        }

        $_SESSION['form_errors'] = $errors;
        $_SESSION['form_old'] = ['name'=>$name,'email'=>$email,'role'=>$role];
        header('Location: users.php');
        exit;
    }

    if ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = normalize_role($_POST['role'] ?? 'user');
        $newpass = $_POST['password'] ?? '';

        if ($id <= 0) {
            $_SESSION['flash'] = 'Invalid user.';
            header('Location: users.php');
            exit;
        }

        if ($id === (int)$_SESSION['user']['id'] && $role !== 'admin') {
            $_SESSION['flash'] = 'You cannot remove your own admin privileges.';
            header('Location: users.php');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $name === '') {
            $_SESSION['flash'] = 'Name and valid email are required.';
            header('Location: users.php');
            exit;
        }

        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
        $chk->execute([$email, $id]);
        if ($chk->fetch()) {
            $_SESSION['flash'] = 'Email already used by another user.';
            header('Location: users.php');
            exit;
        }

        if ($newpass !== '') {
            if (strlen($newpass) < 6) {
                $_SESSION['flash'] = 'New password must be at least 6 characters.';
                header('Location: users.php');
                exit;
            }
            $hash = password_hash($newpass, PASSWORD_DEFAULT);
            $up = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?');
            $up->execute([$name, $email, $role, $hash, $id]);
        } else {
            $up = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?');
            $up->execute([$name, $email, $role, $id]);
        }

        $_SESSION['flash'] = 'User updated.';
        header('Location: users.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash'] = 'Invalid user.';
            header('Location: users.php');
            exit;
        }
        if ($id === (int)$_SESSION['user']['id']) {
            $_SESSION['flash'] = 'You cannot delete your own account.';
            header('Location: users.php');
            exit;
        }
        $del = $pdo->prepare('DELETE FROM users WHERE id = ?');
        $del->execute([$id]);
        $_SESSION['flash'] = 'User deleted.';
        header('Location: users.php');
        exit;
    }

    if ($action === 'toggle_role') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash'] = 'Invalid user.';
            header('Location: users.php');
            exit;
        }
        // Prevent toggling own role off
        if ($id === (int)$_SESSION['user']['id']) {
            $_SESSION['flash'] = 'You cannot change your own admin role here.';
            header('Location: users.php');
            exit;
        }
        $r = $pdo->prepare('SELECT role FROM users WHERE id = ? LIMIT 1');
        $r->execute([$id]);
        $cur = $r->fetchColumn();
        $new = ($cur === 'admin') ? 'user' : 'admin';
        $u = $pdo->prepare('UPDATE users SET role = ? WHERE id = ?');
        $u->execute([$new, $id]);
        $_SESSION['flash'] = 'User role updated.';
        header('Location: users.php');
        exit;
    }

    if ($action === 'toggle_block') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['flash'] = 'Invalid user.';
            header('Location: users.php');
            exit;
        }
        if ($id === (int)$_SESSION['user']['id']) {
            $_SESSION['flash'] = 'You cannot block/unblock yourself.';
            header('Location: users.php');
            exit;
        }
        // Fetch current state
        $s = $pdo->prepare('SELECT is_blocked FROM users WHERE id = ? LIMIT 1');
        $s->execute([$id]);
        $cur = (int)$s->fetchColumn();
        $new = $cur ? 0 : 1;
        $p = $pdo->prepare('UPDATE users SET is_blocked = ? WHERE id = ?');
        $p->execute([$new, $id]);
        $_SESSION['flash'] = $new ? 'User blocked.' : 'User unblocked.';
        header('Location: users.php');
        exit;
    }
}

// Fetch users (include is_blocked safely)
$users = $pdo->query('SELECT id, name, email, role, is_blocked, created_at FROM users ORDER BY created_at DESC')->fetchAll();

// Get any flash messages or form errors
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$formErrors = $_SESSION['form_errors'] ?? [];
$formOld = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_errors'], $_SESSION['form_old']);

include __DIR__ . '/../templates/header.php';
?>

<h2>Users</h2>

<?php if ($flash): ?>
  <div class="alert success"><?php echo htmlspecialchars($flash); ?></div>
<?php endif; ?>

<section style="margin-bottom:18px;">
  <h3>Add new user</h3>

  <?php if (!empty($formErrors)): ?>
    <div class="alert error">
      <?php foreach ($formErrors as $err) echo '<div>' . htmlspecialchars($err) . '</div>'; ?>
    </div>
  <?php endif; ?>

  <form method="post" action="users.php" style="max-width:520px;">
    <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
    <input type="hidden" name="action" value="add">

    <div class="form-row">
      <label>Name</label>
      <input type="text" name="name" value="<?php echo htmlspecialchars($formOld['name'] ?? ''); ?>" required>
    </div>
    <div class="form-row">
      <label>Email</label>
      <input type="email" name="email" value="<?php echo htmlspecialchars($formOld['email'] ?? ''); ?>" required>
    </div>
    <div class="form-row">
      <label>Password</label>
      <input type="password" name="password" required placeholder="At least 6 characters">
    </div>
    <div class="form-row">
      <label>Role</label>
      <select name="role">
        <option value="user" <?php echo (isset($formOld['role']) && $formOld['role']==='user')?'selected':''; ?>>User</option>
        <option value="admin" <?php echo (isset($formOld['role']) && $formOld['role']==='admin')?'selected':''; ?>>Admin</option>
      </select>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn">Add user</button>
      <span class="small">Passwords are hashed. New users can log in immediately.</span>
    </div>
  </form>
</section>

<?php if (empty($users)): ?>
  <p>No users.</p>
<?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Name</th><th>Email</th><th>Role</th><th>Blocked</th><th>Joined</th><th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?php echo htmlspecialchars($u['name']); ?></td>
          <td><?php echo htmlspecialchars($u['email']); ?></td>
          <td><?php echo htmlspecialchars($u['role']); ?></td>
          <td><?php echo $u['is_blocked'] ? 'Yes' : 'No'; ?></td>
          <td><?php echo htmlspecialchars($u['created_at']); ?></td>
          <td>
            <a href="users.php?edit=<?php echo (int)$u['id']; ?>" class="btn ghost">Edit</a>

            <form method="post" action="users.php" style="display:inline-block; margin:0 6px;">
              <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
              <input type="hidden" name="action" value="toggle_role">
              <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
              <button type="submit" class="btn ghost"><?php echo $u['role'] === 'admin' ? 'Demote' : 'Promote'; ?></button>
            </form>

            <form method="post" action="users.php" style="display:inline-block; margin:0 6px;">
              <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
              <input type="hidden" name="action" value="toggle_block">
              <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
              <button type="submit" class="btn ghost"><?php echo $u['is_blocked'] ? 'Unblock' : 'Block'; ?></button>
            </form>

            <form method="post" action="users.php" style="display:inline-block; margin:0 6px;" onsubmit="return confirm('Delete this user? This action cannot be undone.');">
              <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
              <button type="submit" class="btn ghost" style="color:#b91c1c;">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<?php
if (isset($_GET['edit'])):
    $editId = (int)$_GET['edit'];
    $q = $pdo->prepare('SELECT id, name, email, role, is_blocked FROM users WHERE id = ? LIMIT 1');
    $q->execute([$editId]);
    $editUser = $q->fetch();
    if ($editUser):
?>
  <hr style="margin:18px 0;">
  <section>
    <h3>Edit user: <?php echo htmlspecialchars($editUser['name']); ?></h3>
    <form method="post" action="users.php" style="max-width:520px;">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf); ?>">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id" value="<?php echo (int)$editUser['id']; ?>">

      <div class="form-row">
        <label>Name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($editUser['name']); ?>" required>
      </div>
      <div class="form-row">
        <label>Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($editUser['email']); ?>" required>
      </div>
      <div class="form-row">
        <label>Role</label>
        <select name="role">
          <option value="user" <?php echo $editUser['role'] === 'user' ? 'selected' : ''; ?>>User</option>
          <option value="admin" <?php echo $editUser['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
        </select>
      </div>
      <div class="form-row">
        <label>New password (leave blank to keep current)</label>
        <input type="password" name="password" placeholder="New password (optional)">
      </div>

      <div class="form-actions">
        <button type="submit" class="btn">Save changes</button>
        <a href="users.php" class="btn secondary">Cancel</a>
      </div>
    </form>
  </section>
<?php
    endif;
endif;
?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
