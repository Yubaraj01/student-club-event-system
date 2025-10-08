<?php
if (!session_id()) session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $del = $pdo->prepare('DELETE FROM events WHERE id = ?');
    $del->execute([$id]);
    $_SESSION['flash'] = 'Event deleted.';
    header('Location: events.php');
    exit;
}

$events = $pdo->query('SELECT * FROM events ORDER BY event_date DESC')->fetchAll();

include __DIR__ . '/../templates/header.php';
?>
<h2>Manage events</h2>
<p><a href="../events/create.php" class="btn">Create new event</a></p>

<?php if (!empty($_SESSION['flash'])): ?>
  <div class="alert success"><?php echo htmlspecialchars($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
<?php endif; ?>

<?php if (empty($events)): ?>
  <p>No events yet.</p>
<?php else: ?>
  <?php foreach ($events as $e): ?>
    <div class="event">
      <h3><?php echo htmlspecialchars($e['title']); ?></h3>
      <div class="small">Date: <?php echo htmlspecialchars($e['event_date']); ?> | Capacity: <?php echo (int)$e['capacity']; ?></div>
      <p><a href="../events/view.php?id=<?php echo (int)$e['id']; ?>">View</a> | <a href="events.php?delete=<?php echo (int)$e['id']; ?>">Delete</a></p>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
