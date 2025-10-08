<?php
if (!session_id()) session_start();
require_once __DIR__ . '/../../config/db.php';

// Only admin can create events
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

$errors = [];
$title = '';
$description = '';
$event_date = '';
$capacity = 50;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date = trim($_POST['event_date'] ?? '');
    $capacity = (int)($_POST['capacity'] ?? 0);

    if ($title === '') $errors[] = 'Title required';
    if ($event_date === '') $errors[] = 'Date required';

    if (!$errors) {
        $ins = $pdo->prepare('INSERT INTO events (title, description, event_date, capacity) VALUES (?, ?, ?, ?)');
        $ins->execute([$title, $description, $event_date, $capacity]);
        $_SESSION['flash'] = 'Event created.';
        header('Location: ../admin/events.php');
        exit;
    }
}

include __DIR__ . '/../templates/header.php';
?>
<h2>Create event (Admin)</h2>

<?php if ($errors): ?>
  <div class="alert error"><?php foreach($errors as $e) echo '<div>' . htmlspecialchars($e) . '</div>'; ?></div>
<?php endif; ?>

<form method="post" action="create.php">
  <div class="form-row">
    <label>Title</label>
    <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
  </div>
  <div class="form-row">
    <label>Date</label>
    <input type="date" name="event_date" value="<?php echo htmlspecialchars($event_date); ?>" required>
  </div>
  <div class="form-row">
    <label>Capacity</label>
    <input type="number" name="capacity" value="<?php echo htmlspecialchars($capacity); ?>">
  </div>
  <div class="form-row">
    <label>Description</label>
    <textarea name="description" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
  </div>
  <button type="submit" class="btn">Create event</button>
</form>

<?php include __DIR__ . '/../templates/footer.php'; ?>
