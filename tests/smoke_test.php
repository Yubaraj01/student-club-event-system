<?php
require_once __DIR__ . '/../config/db.php';
$failures = 0;
function ok($m){ echo "PASS: $m" . PHP_EOL; }
function warn($m){ echo "WARN: $m" . PHP_EOL; }
function fail($m){ echo "FAIL: $m" . PHP_EOL; }
try {
    $pdo->query('SELECT 1')->fetchColumn();
    ok('Database connection established');
} catch (Exception $e) {
    fail('Database connection failed: ' . $e->getMessage());
    exit(1);
}
try {
    $dbName = $pdo->query('SELECT DATABASE()')->fetchColumn();
    if (!$dbName) { fail('Unable to determine current database (SELECT DATABASE())'); exit(1); } else { ok("Connected to database: $dbName"); }
} catch (Exception $e) { fail('Error determining database name: ' . $e->getMessage()); exit(1); }
$requiredTables = ['users','events','registrations'];
try {
    $stmt = $pdo->prepare("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?");
    foreach ($requiredTables as $t) {
        $stmt->execute([$dbName, $t]);
        $row = $stmt->fetchColumn();
        if ($row === false) { fail("Missing table: $t"); $failures++; } else { ok("Table exists: $t"); }
    }
} catch (Exception $e) { fail('Error checking tables: ' . $e->getMessage()); exit(1); }
try { $usersCount = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(); ok("Users count: $usersCount"); } catch (Exception $e) { fail('Error counting users: ' . $e->getMessage()); $failures++; }
try { $eventsCount = (int)$pdo->query('SELECT COUNT(*) FROM events')->fetchColumn(); ok("Events count: $eventsCount"); } catch (Exception $e) { fail('Error counting events: ' . $e->getMessage()); $failures++; }
try { $regsCount = (int)$pdo->query('SELECT COUNT(*) FROM registrations')->fetchColumn(); ok("Registrations count: $regsCount"); } catch (Exception $e) { fail('Error counting registrations: ' . $e->getMessage()); $failures++; }
try { $upcoming = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE event_date >= CURDATE()")->fetchColumn(); ok("Upcoming events (event_date >= today): $upcoming"); } catch (Exception $e) { fail('Error checking upcoming events: ' . $e->getMessage()); $failures++; }
try {
    $adminCount = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
    if ($adminCount >= 1) { ok("Admin accounts present: $adminCount"); } else { warn('No admin account found (expect at least one admin)'); }
} catch (Exception $e) { fail('Error checking admin accounts: ' . $e->getMessage()); $failures++; }
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'is_blocked'");
    $stmt->execute([$dbName]);
    $hasIsBlocked = (int)$stmt->fetchColumn();
    if ($hasIsBlocked) { ok("Column users.is_blocked exists"); } else { warn("Column users.is_blocked does not exist (blocking functionality may be unavailable)"); }
} catch (Exception $e) { fail('Error checking users.is_blocked column: ' . $e->getMessage()); $failures++; }
try {
    $stmt = $pdo->prepare("
        SELECT tc.CONSTRAINT_NAME
        FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
        JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu ON tc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA AND tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME AND tc.TABLE_NAME = kcu.TABLE_NAME
        WHERE tc.CONSTRAINT_TYPE = 'UNIQUE' AND tc.TABLE_SCHEMA = ? AND tc.TABLE_NAME = 'registrations'
        GROUP BY tc.CONSTRAINT_NAME
        HAVING SUM(kcu.COLUMN_NAME IN ('user_id','event_id')) = 2
    ");
    $stmt->execute([$dbName]);
    $row = $stmt->fetchColumn();
    if ($row) { ok("Unique constraint on registrations(user_id,event_id) found ($row)"); } else { warn("No UNIQUE constraint found for registrations(user_id,event_id) — duplicate registrations may be possible"); }
} catch (Exception $e) { fail('Error checking unique constraint on registrations: ' . $e->getMessage()); $failures++; }
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = 'registrations'");
    $stmt->execute([$dbName]);
    $fkCount = (int)$stmt->fetchColumn();
    if ($fkCount >= 2) { ok("Foreign keys on registrations exist (count: $fkCount)"); } else { warn("Foreign keys on registrations appear incomplete (found: $fkCount)"); }
} catch (Exception $e) { fail('Error checking foreign keys for registrations: ' . $e->getMessage()); $failures++; }
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE password IS NULL OR password = ''");
    $badPasswords = (int)$stmt->fetchColumn();
    if ($badPasswords === 0) { ok("All users have non-empty password hashes"); } else { warn("Found $badPasswords user(s) with empty password field"); }
} catch (Exception $e) { fail('Error checking password column: ' . $e->getMessage()); $failures++; }
$uploads = __DIR__ . '/../uploads';
if (is_dir($uploads)) { if (is_writable($uploads)) { ok("Uploads directory exists and is writable: $uploads"); } else { warn("Uploads directory exists but is not writable: $uploads"); } } else { warn("Uploads directory does not exist: $uploads"); }
try {
    $stmt = $pdo->prepare('SELECT id, email, name FROM users ORDER BY id ASC LIMIT 3');
    $stmt->execute();
    $sample = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($sample) > 0) {
        ok('Sample user rows found: showing up to 3 entries');
        foreach ($sample as $u) { echo "  - user id=" . (int)$u['id'] . " email=" . $u['email'] . " name=" . $u['name'] . PHP_EOL; }
    } else { warn('No users found in users table'); }
} catch (Exception $e) { fail('Error fetching sample users: ' . $e->getMessage()); $failures++; }
try { if (session_status() !== PHP_SESSION_ACTIVE) session_start(); $_SESSION['__smoke_test'] = 'ok'; $val = $_SESSION['__smoke_test'] ?? null; if ($val === 'ok') { ok('Session start and session storage working'); unset($_SESSION['__smoke_test']); } else { warn('Session test could not verify value persistence'); } } catch (Exception $e) { fail('Error testing PHP sessions: ' . $e->getMessage()); $failures++; }
try { $p = $pdo->prepare('SELECT id FROM events ORDER BY id DESC LIMIT 1'); $p->execute(); $lastEvent = $p->fetchColumn(); if ($lastEvent) { ok("Events table appears to have entries (latest id: $lastEvent)"); } else { warn('Events table appears empty'); } } catch (Exception $e) { fail('Error looking up events: ' . $e->getMessage()); $failures++; }
if ($failures === 0) { ok('All smoke tests passed'); exit(0); } else { fail("Smoke tests completed with $failures warning/failure(s) (review output above)"); exit(1); }
