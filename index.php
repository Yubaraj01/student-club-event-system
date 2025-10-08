<?php
include __DIR__ . '/src/templates/header.php';

$base = $GLOBALS['BASE_PATH'] ?? '';

require_once __DIR__ . '/config/db.php';

try {
    $stmt = $pdo->prepare("SELECT id, title, description, event_date, capacity FROM events WHERE event_date >= CURDATE() ORDER BY event_date ASC LIMIT 4");
    $stmt->execute();
    $upcoming = $stmt->fetchAll();
} catch (Exception $e) {
    $upcoming = [];
}

function url($path) {
    $b = $GLOBALS['BASE_PATH'] ?? '';
    if ($b === '') return $path;
    // if $path already begins with '/', remove it to avoid '//' when concatenating
    return $b . (strpos($path, '/') === 0 ? $path : '/' . $path);
}

function excerpt($text, $len = 140) {
    $t = trim($text);
    if (strlen($t) <= $len) return htmlspecialchars($t);
    $short = substr($t, 0, $len);
    $pos = strrpos($short, ' ');
    if ($pos !== false) $short = substr($short, 0, $pos);
    return htmlspecialchars($short) . '…';
}

?>
<section style="display:flex; gap:18px; align-items:center; justify-content:space-between; flex-wrap:wrap;">
  <div style="flex:1; min-width:260px;">
    <h1>Student Club Event Registration</h1>
    <p class="small">Browse upcoming club events, sign up for activities, and manage participation all in one simple system. You must be logged in to register or manage events.</p>

    <div class="form-actions" style="margin-top:16px;">
      <a class="btn accent" href="<?php echo htmlspecialchars(url('/src/events/list.php'), ENT_QUOTES); ?>">Browse events</a>
      <?php if (isset($_SESSION['user'])): ?>
        <a class="btn" href="<?php echo htmlspecialchars(url('/src/auth/logout.php'), ENT_QUOTES); ?>">Logout</a>
      <?php else: ?>
        <a class="btn" href="<?php echo htmlspecialchars(url('/src/auth/register.php'), ENT_QUOTES); ?>">Get started</a>
        <a class="btn secondary" href="<?php echo htmlspecialchars(url('/src/auth/login.php'), ENT_QUOTES); ?>">Login</a>
      <?php endif; ?>
    </div>

  </div>

  <div style="width:320px; min-width:220px;">
    <div class="event" aria-hidden="true">
      <h3>Quick tips</h3>
      <ul class="small" style="margin-top:8px;">
        <li>Register and then confirm your account by logging in.</li>
        <li>Admins can create and manage events from the Admin dashboard.</li>
        <li>Capacity limits prevent overbooking, you’ll see “full” when an event is at capacity.</li>
      </ul>
    </div>
  </div>
</section>

<hr style="margin:18px 0; border:none; border-top:1px solid var(--border);">

<section style="display:grid; grid-template-columns: repeat(auto-fit, minmax(220px,1fr)); gap:12px; margin-bottom:18px;">
  <div class="event">
    <h3>How it works</h3>
    <ol class="small" style="margin-top:8px; padding-left:18px;">
      <li>Create an account (Register).</li>
      <li>Browse events and view details.</li>
      <li>Login and click “Sign up” on an event page to register.</li>
    </ol>
  </div>

  <div class="event">
    <h3>Who can use this</h3>
    <p class="small">Students and club officers. Admins are responsible for creating events and managing registrations.</p>
  </div>
</section>

<section>
  <h2>Upcoming events</h2>

  <?php if (empty($upcoming)): ?>
    <div class="event small">No upcoming events. If you're an admin, please create events from the <a href="<?php echo htmlspecialchars(url('/src/admin/dashboard.php'), ENT_QUOTES); ?>">Admin dashboard</a>.</div>
  <?php else: ?>
    <div style="display:grid; gap:12px; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
      <?php foreach ($upcoming as $ev): ?>
        <article class="event" aria-labelledby="ev-<?php echo (int)$ev['id']; ?>">
          <h3 id="ev-<?php echo (int)$ev['id']; ?>"><?php echo htmlspecialchars($ev['title']); ?></h3>
          <div class="small">Date: <?php echo htmlspecialchars($ev['event_date']); ?> &middot; Capacity: <?php echo (int)$ev['capacity']; ?></div>
          <p class="small" style="margin-top:8px;"><?php echo excerpt($ev['description'] ?? '', 140); ?></p>

          <div class="actions" style="margin-top:10px;">
            <?php if (isset($_SESSION['user'])): ?>
              <!-- logged in users go to event view (where they can register) -->
              <a class="btn" href="<?php echo htmlspecialchars(url('/src/events/view.php') . '?id=' . (int)$ev['id'], ENT_QUOTES); ?>">View / Register</a>
            <?php else: ?>
              <!-- public users must log in -->
              <a class="btn secondary" href="<?php echo htmlspecialchars(url('/src/auth/login.php'), ENT_QUOTES); ?>" title="Login to register">Login to register</a>
            <?php endif; ?>
            <a class="btn ghost" href="<?php echo htmlspecialchars(url('/src/events/view.php') . '?id=' . (int)$ev['id'], ENT_QUOTES); ?>">Details</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<hr style="margin:18px 0; border:none; border-top:1px solid var(--border);">

<section style="display:grid; grid-template-columns: 1fr 300px; gap:12px;">
  <div class="event">
    <h3>About this system</h3>
    <p class="small">This Student Club Event Registration System was implemented for the assignment. It demonstrates authentication, user roles (user/admin), event management, registration flow, and basic security best-practices.</p>
    <p class="small">For grading: import the SQL schema, create or promote an admin, and test the flows. See the installation & user manual in the project documentation.</p>
  </div>

  <aside class="event">
    <h3>Contact / Support</h3>
    <p class="small">If you encounter problems running the app locally: ensure Apache & MySQL are running, import the SQL schema, and update `config/config.php` with your DB credentials.</p>
    <p class="small"><strong>Quick support:</strong> Use the "Register" link to create a test user, then promote to admin via phpMyAdmin to access admin features.</p>
  </aside>
</section>

<?php include __DIR__ . '/src/templates/footer.php'; ?>
