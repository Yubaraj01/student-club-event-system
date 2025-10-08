<?php
if (!session_id()) session_start();
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../auth/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['event_id'])) {
    header('Location: list.php');
    exit;
}

$event_id = (int)$_POST['event_id'];
$user_id = (int)$_SESSION['user']['id'];

$stmt = $pdo->prepare('SELECT capacity FROM events WHERE id = ? LIMIT 1');
$stmt->execute([$event_id]);
$event = $stmt->fetch();
if (!$event) {
    $_SESSION['flash'] = 'Event not found.';
    header('Location: list.php');
    exit;
}

$check = $pdo->prepare('SELECT id FROM registrations WHERE user_id = ? AND event_id = ? LIMIT 1');
$check->execute([$user_id, $event_id]);
if ($check->fetch()) {
    $_SESSION['flash'] = 'You are already registered for this event.';
    header('Location: view.php?id=' . $event_id);
    exit;
}

$count = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE event_id = ?');
$count->execute([$event_id]);
$current = (int)$count->fetchColumn();
if ($current >= (int)$event['capacity']) {
    $_SESSION['flash'] = 'Event is full.';
    header('Location: view.php?id=' . $event_id);
    exit;
}

$ins = $pdo->prepare('INSERT INTO registrations (user_id, event_id) VALUES (?, ?)');
$ins->execute([$user_id, $event_id]);
$_SESSION['flash'] = 'Registration successful.';
header('Location: view.php?id=' . $event_id);
exit;
