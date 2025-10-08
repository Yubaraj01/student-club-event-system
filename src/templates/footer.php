<?php
if (!empty($GLOBALS['FOOTER_RENDERED'])) {
    return;
}
$GLOBALS['FOOTER_RENDERED'] = true;

$base = $GLOBALS['BASE_PATH'] ?? '';
if ($base === '') {
    $jsPath = '/assets/js/app.js';
} else {
    $jsPath = $base . '/assets/js/app.js';
}
?>
</main>

<footer class="container" role="contentinfo">
  <div class="footer-note">Student Club Event Registration System : ICT312 – Advanced Web Information Systems </div>
</footer>

<script src="<?php echo htmlspecialchars($jsPath, ENT_QUOTES); ?>"></script>
</body>
</html>
