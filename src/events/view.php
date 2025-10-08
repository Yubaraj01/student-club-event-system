<?php
if (!session_id()) session_start();
require_once __DIR__ . '/../../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: list.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM events WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$event = $stmt->fetch();

if (!$event) {
    include __DIR__ . '/../templates/header.php';
    echo '<div class="alert error">Event not found.</div>';
    include __DIR__ . '/../templates/footer.php';
    exit;
}

$r = $pdo->prepare('SELECT COUNT(*) as c FROM registrations WHERE event_id = ?');
$r->execute([$id]);
$regCount = (int)$r->fetchColumn();

include __DIR__ . '/../templates/header.php';
?>
<h2><?php echo htmlspecialchars($event['title']); ?></h2>
<p class="small">Date: <?php echo htmlspecialchars($event['event_date']); ?> | Capacity: <?php echo (int)$event['capacity']; ?></p>
<p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>

<p class="small">Registered: <?php echo $regCount; ?></p>

<?php if (!isset($_SESSION['user'])): ?>
  <div class="alert">
    Please <a href="../auth/login.php">login</a> or <a href="../auth/register.php">register</a> to sign up.
  </div>
<?php else: ?>
  <?php if ($regCount >= (int)$event['capacity']): ?>
    <div class="alert error">This event is full.</div>
  <?php else: ?>
    <form method="post" action="signup.php">
      <input type="hidden" name="event_id" value="<?php echo (int)$event['id']; ?>">
      <button type="submit" class="btn">Sign up</button>
    </form>
  <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../templates/footer.php'; ?>
