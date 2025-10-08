<?php
if (!session_id()) session_start();
require_once __DIR__ . '/../../config/db.php';

// Admin guard
if (!isset($_SESSION['user']) || ($_SESSION['user']['role'] ?? '') !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}

try {
    $usersCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
} catch (Exception $e) {
    $usersCount = 0;
}
try {
    $eventsCount = (int)$pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
} catch (Exception $e) {
    $eventsCount = 0;
}
try {
    $regsCount = (int)$pdo->query('SELECT COUNT(*) FROM registrations')->fetchColumn();
} catch (Exception $e) {
    $regsCount = 0;
}

$roleCounts = ['admin' => 0, 'user' => 0];
try {
    $stmt = $pdo->query("SELECT role, COUNT(*) AS c FROM users GROUP BY role");
    while ($r = $stmt->fetch()) {
        $role = $r['role'] ?? 'user';
        $roleCounts[$role] = (int)$r['c'];
    }
} catch (Exception $e) { /* ignore */ }

$topEvents = [];
try {
    $stmt = $pdo->query("
      SELECT e.id, e.title, e.event_date, e.capacity, COUNT(r.id) AS regs
      FROM events e
      LEFT JOIN registrations r ON r.event_id = e.id
      GROUP BY e.id
      ORDER BY regs DESC, e.event_date ASC
      LIMIT 6
    ");
    $topEvents = $stmt->fetchAll();
} catch (Exception $e) { $topEvents = []; }

$recentRegs = [];
try {
    $stmt = $pdo->query("
      SELECT r.id, r.created_at, u.name AS user_name, u.email AS user_email, e.title AS event_title
      FROM registrations r
      JOIN users u ON u.id = r.user_id
      JOIN events e ON e.id = r.event_id
      ORDER BY r.created_at DESC
      LIMIT 8
    ");
    $recentRegs = $stmt->fetchAll();
} catch (Exception $e) { $recentRegs = []; }

$recentEvents = [];
try {
    $stmt = $pdo->query("SELECT id, title, event_date, capacity, created_at FROM events ORDER BY created_at DESC LIMIT 6");
    $recentEvents = $stmt->fetchAll();
} catch (Exception $e) { $recentEvents = []; }

$recentUsers = [];
try {
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 6");
    $recentUsers = $stmt->fetchAll();
} catch (Exception $e) { $recentUsers = []; }

// Expose arrays to JS (json)
$jsTopEvents = json_encode(array_map(function($e){
    return ['title'=>$e['title'],'regs'=>(int)$e['regs']];
}, $topEvents));
$jsRoleCounts = json_encode($roleCounts);

include __DIR__ . '/../templates/header.php';
?>

<h2>Admin dashboard</h2>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:14px; margin-bottom:18px;">
  <div class="event" aria-live="polite">
    <h3>Users</h3>
    <div class="small">Total registered users</div>
    <div style="font-size:1.6rem; font-weight:700; margin-top:8px;"><?php echo (int)$usersCount; ?></div>
    <div class="mt-8"><a class="btn ghost" href="users.php">Manage users</a></div>
  </div>

  <div class="event" aria-live="polite">
    <h3>Events</h3>
    <div class="small">Active events in system</div>
    <div style="font-size:1.6rem; font-weight:700; margin-top:8px;"><?php echo (int)$eventsCount; ?></div>
    <div class="mt-8"><a class="btn ghost" href="events.php">Manage events</a></div>
  </div>

  <div class="event" aria-live="polite">
    <h3>Registrations</h3>
    <div class="small">Total registrations</div>
    <div style="font-size:1.6rem; font-weight:700; margin-top:8px;"><?php echo (int)$regsCount; ?></div>
    <div class="mt-8"><a class="btn ghost" href="../events/list.php">View events</a></div>
  </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 360px; gap:18px; align-items:start;">

  <div>
    <div class="event" style="margin-bottom:14px;">
      <h3>Top events by registrations</h3>
      <div class="small">Shows the events with the most sign-ups (top 6)</div>

      <?php if (empty($topEvents)): ?>
        <div class="mt-8 small">No events or registrations yet.</div>
      <?php else: ?>
        <div style="margin-top:12px;">
          <?php
            $max = 1;
            foreach ($topEvents as $te) { $max = max($max, (int)$te['regs']); }
            $max = max($max, 1);
          ?>
          <svg viewBox="0 0 600 <?php echo 36 * count($topEvents); ?>" width="100%" role="img" aria-label="Top events by registrations">
            <?php foreach ($topEvents as $i => $te):
              $y = 18 + ($i * 36);
              $label = htmlspecialchars($te['title']);
              $val = (int)$te['regs'];
              $w = round(($val / $max) * 480); 
            ?>
              <text x="6" y="<?php echo $y + 6; ?>" font-size="12" fill="#0b1220" alignment-baseline="middle"><?php echo $label; ?></text>
              <rect x="6" y="<?php echo $y + 10; ?>" rx="8" ry="8" width="520" height="10" fill="#f1f5f9"></rect>
              <rect x="6" y="<?php echo $y + 10; ?>" rx="8" ry="8" width="<?php echo $w; ?>" height="10" fill="#006A67"></rect>
              <text x="<?php echo 6 + $w + 8; ?>" y="<?php echo $y + 18; ?>" font-size="12" fill="#0b1220"><?php echo $val; ?></text>
            <?php endforeach; ?>
          </svg>
        </div>
      <?php endif; ?>

    </div>

    <div class="event" aria-live="polite">
      <h3>Recent registrations</h3>
      <div class="small">Latest sign-ups (most recent first)</div>

      <?php if (empty($recentRegs)): ?>
        <div class="mt-8 small">No registrations yet.</div>
      <?php else: ?>
        <ul style="margin-top:12px; list-style:none; padding:0; display:flex; flex-direction:column; gap:8px;">
          <?php foreach ($recentRegs as $r): ?>
            <li style="padding:8px; border-radius:8px; border:1px solid var(--border); background:#fff;">
              <div style="display:flex; justify-content:space-between; gap:12px; align-items:center;">
                <div>
                  <strong><?php echo htmlspecialchars($r['user_name']); ?></strong>
                  <div class="small"><?php echo htmlspecialchars($r['user_email']); ?> — <em><?php echo htmlspecialchars($r['event_title']); ?></em></div>
                </div>
                <div class="small"><?php echo htmlspecialchars($r['created_at']); ?></div>
              </div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <aside>
    <div class="event" style="text-align:center;">
      <h3>Users by role</h3>
      <div class="small">Admin vs User</div>

      <?php
        $totalRoles = array_sum($roleCounts) ?: 1;
        $adminPct = round(($roleCounts['admin'] / $totalRoles) * 100);
        $userPct = 100 - $adminPct;
        $donut = "conic-gradient(#003161 {$adminPct}%, #006A67 {$adminPct}% 100%)";
      ?>

      <div style="display:flex; gap:10px; align-items:center; justify-content:center; margin-top:12px;">
        <div style="width:120px; height:120px; border-radius:999px; background:<?php echo $donut; ?>; box-shadow:var(--shadow-sm); display:grid; place-items:center;">
          <div style="background:#fff; width:64px; height:64px; border-radius:999px; display:grid; place-items:center; font-weight:700;">
            <?php echo (int)$totalRoles; ?>
          </div>
        </div>
      </div>

      <div style="margin-top:12px; display:flex; gap:8px; justify-content:center;">
        <div class="small"><span style="display:inline-block;width:12px;height:12px;background:#003161;border-radius:3px;margin-right:6px;"></span>Admins: <?php echo (int)$roleCounts['admin']; ?></div>
        <div class="small"><span style="display:inline-block;width:12px;height:12px;background:#006A67;border-radius:3px;margin-right:6px;"></span>Users: <?php echo (int)$roleCounts['user']; ?></div>
      </div>
    </div>

    <div class="event" style="margin-top:14px;">
      <h3>Recent events</h3>
      <?php if (empty($recentEvents)): ?>
        <div class="small mt-8">No events yet.</div>
      <?php else: ?>
        <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:8px;">
          <?php foreach ($recentEvents as $ev): ?>
            <li style="padding:8px; border-radius:8px; border:1px solid var(--border);">
              <strong><?php echo htmlspecialchars($ev['title']); ?></strong>
              <div class="small"><?php echo htmlspecialchars($ev['event_date']); ?> — capacity <?php echo (int)$ev['capacity']; ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>

    <div class="event" style="margin-top:14px;">
      <h3>Recent users</h3>
      <?php if (empty($recentUsers)): ?>
        <div class="small mt-8">No users yet.</div>
      <?php else: ?>
        <ul style="list-style:none; padding:0; margin:0; display:flex; flex-direction:column; gap:8px;">
          <?php foreach ($recentUsers as $u): ?>
            <li style="padding:8px; border-radius:8px; border:1px solid var(--border);">
              <strong><?php echo htmlspecialchars($u['name']); ?></strong>
              <div class="small"><?php echo htmlspecialchars($u['email']); ?></div>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </aside>
</div>

<?php include __DIR__ . '/../templates/footer.php'; ?>

<script>
  (function(){
    try {
      var top = <?php echo $jsTopEvents ?: '[]'; ?>;
      var roles = <?php echo $jsRoleCounts ?: '{}'; ?>;
      console.debug('Dashboard top events', top, 'roles', roles);
    } catch(e) {
      console.error(e);
    }
  })();
</script>
