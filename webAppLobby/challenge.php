<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=not_logged_in');
    exit;
}

require_once 'config.php';

$userId = (int) $_SESSION['user_id'];
$action = (string) ($_POST['action'] ?? '');

if ($action === 'send') {
    $challengedId = (int) ($_POST['challenged_id'] ?? 0);

    if ($challengedId <= 0 || $challengedId === $userId) {
        header('Location: lobby.php?msg=Invalid+opponent.');
        exit;
    }

    // Verify the target user exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE id = ?');
    $stmt->execute([$challengedId]);
    if (!$stmt->fetch()) {
        header('Location: lobby.php?msg=Player+not+found.');
        exit;
    }

    // Block duplicate pending challenges
    $stmt = $pdo->prepare(
        "SELECT id FROM challenges WHERE challenger_id = ? AND challenged_id = ? AND status = 'pending'"
    );
    $stmt->execute([$userId, $challengedId]);
    if ($stmt->fetch()) {
        header('Location: lobby.php?msg=You+already+have+a+pending+challenge+against+that+player.');
        exit;
    }

    $stmt = $pdo->prepare('INSERT INTO challenges (challenger_id, challenged_id) VALUES (?, ?)');
    $stmt->execute([$userId, $challengedId]);
    header('Location: lobby.php?msg=Challenge+sent!');
    exit;
}

if ($action === 'accept') {
    $challengeId = (int) ($_POST['challenge_id'] ?? 0);

    $stmt = $pdo->prepare(
        "SELECT * FROM challenges WHERE id = ? AND challenged_id = ? AND status = 'pending'"
    );
    $stmt->execute([$challengeId, $userId]);
    $challenge = $stmt->fetch();

    if (!$challenge) {
        header('Location: lobby.php?msg=Challenge+not+found+or+already+resolved.');
        exit;
    }

    $pdo->beginTransaction();

    // Challenger plays X (goes first), challenged plays O
    $stmt = $pdo->prepare('INSERT INTO games (player_x_id, player_o_id) VALUES (?, ?)');
    $stmt->execute([(int) $challenge['challenger_id'], $userId]);
    $gameId = (int) $pdo->lastInsertId();

    $stmt = $pdo->prepare("UPDATE challenges SET status = 'accepted' WHERE id = ?");
    $stmt->execute([$challengeId]);

    $pdo->commit();

    header("Location: game.php?id={$gameId}");
    exit;
}

if ($action === 'decline') {
    $challengeId = (int) ($_POST['challenge_id'] ?? 0);

    $stmt = $pdo->prepare(
        "UPDATE challenges SET status = 'declined' WHERE id = ? AND challenged_id = ?"
    );
    $stmt->execute([$challengeId, $userId]);
    header('Location: lobby.php?msg=Challenge+declined.');
    exit;
}

header('Location: lobby.php');
exit;
