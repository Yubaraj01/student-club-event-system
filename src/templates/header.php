<?php

if (!session_id()) session_start();

if (!empty($GLOBALS['HEADER_RENDERED'])) {
    return;
}
$GLOBALS['HEADER_RENDERED'] = true;

$script = $_SERVER['SCRIPT_NAME'] ?? '';
if (strpos($script, '/src') !== false) {
    $base = substr($script, 0, strpos($script, '/src'));
} else {
    $base = dirname($script);
}
$base = rtrim($base, '/');
$GLOBALS['BASE_PATH'] = $base;

$cssPath  = ($base === '') ? '/assets/css/styles.css' : $base . '/assets/css/styles.css';
$jsPath   = ($base === '') ? '/assets/js/app.js'    : $base . '/assets/js/app.js';
$indexUrl = ($base === '') ? '/index.php'           : $base . '/index.php';

$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$requestPath = parse_url($requestUri, PHP_URL_PATH) ?: '';

function bp($path) {
    $b = $GLOBALS['BASE_PATH'] ?? '';
    if ($b === '') return $path;
    // ensure $path begins with '/'
    if (strpos($path, '/') !== 0) $path = '/' . $path;
    return $b . $path;
}

function is_active($path) {
    global $requestPath;
    $full = bp($path);
    return stripos($requestPath, $full) === 0;
}

$user = $_SESSION['user'] ?? null;
$isAdmin = ($user['role'] ?? '') === 'admin';

?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Student Club — Events</title>

  <link rel="stylesheet" href="<?php echo htmlspecialchars($cssPath, ENT_QUOTES); ?>">
  <style>body{visibility:visible}</style>
</head>
<body>
<a href="#main-content" class="btn ghost" style="position:absolute; left:-9999px;" onfocus="this.style.left='12px';" onblur="this.style.left='-9999px';">Skip to main</a>

<header class="container" role="banner" aria-label="Main header">
  <nav class="navbar" role="navigation" aria-label="Main navigation">
    <a href="<?php echo htmlspecialchars($indexUrl, ENT_QUOTES); ?>" class="brand" title="Home">
      <span class="logo" aria-hidden="true">SC</span>
      <span style="line-height:1;">
        <strong style="display:block; font-size:1rem; color:var(--brand-900)">Student Club</strong>
        <small class="small">Events</small>
      </span>
    </a>

    <div style="flex:1"></div>

    <div class="navbar" role="menubar" aria-label="Primary links" style="gap:8px;">
      <a href="<?php echo htmlspecialchars(bp('/index.php'), ENT_QUOTES); ?>" class="btn"<?php if (is_active('/index.php')) echo ' aria-current="page"'; ?>>Home</a>

      <a href="<?php echo htmlspecialchars(bp('/src/events/list.php'), ENT_QUOTES); ?>" class="btn"<?php if (is_active('/src/events/list.php')) echo ' aria-current="page"'; ?>>Events</a>

      <?php if ($user): ?>
        <a href="<?php echo htmlspecialchars(bp('/src/events/list.php') . '?mine=1', ENT_QUOTES); ?>" class="btn">My registrations</a>

        <?php if ($isAdmin): ?>
          <a href="<?php echo htmlspecialchars(bp('/src/admin/dashboard.php'), ENT_QUOTES); ?>" class="btn">Dashboard</a>
        <?php endif; ?>

        <span class="small" style="align-self:center; margin-left:8px; margin-right:8px;">Signed in as <?php echo htmlspecialchars($user['name']); ?></span>

        <a href="<?php echo htmlspecialchars(bp('/src/auth/logout.php'), ENT_QUOTES); ?>" class="btn">Logout</a>
      <?php else: ?>
        <a href="<?php echo htmlspecialchars(bp('/src/auth/login.php'), ENT_QUOTES); ?>" class="btn">Login</a>
        <a href="<?php echo htmlspecialchars(bp('/src/auth/register.php'), ENT_QUOTES); ?>" class="btn">Register</a>
      <?php endif; ?>
    </div>
  </nav>
</header>

<!-- main open (pages render inside here) -->
<main id="main-content" class="container" role="main" tabindex="-1">

<!-- BASE_PATH for debugging -->
<!-- BASE_PATH: <?php echo htmlspecialchars($base, ENT_QUOTES); ?> -->
