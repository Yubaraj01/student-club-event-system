<?php
if (!session_id()) session_start();
require_once __DIR__ . '/../../config/db.php';

$stmt = $pdo->query('SELECT id, title, event_date, capacity FROM events ORDER BY event_date ASC');
$events = $stmt->fetchAll();

include __DIR__ . '/../templates/header.php';
?>
<h2>Upcoming Events</h2>

<?php if (empty($events)): ?>
  <p>No upcoming events. (Admins can <a href="../admin/events.php">create one</a>.)</p>
<?php else: ?>
  <?php foreach ($events as $e): ?>
    <div class="event">
      <h3><?php echo htmlspecialchars($e['title']); ?></h3>
      <div class="small">Date: <?php echo htmlspecialchars($e['event_date']); ?> | Capacity: <?php echo (int)$e['capacity']; ?></div>
      <p><a href="view.php?id=<?php echo (int)$e['id']; ?>">View / Register</a></p>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
