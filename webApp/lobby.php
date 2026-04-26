<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=not_logged_in');
    exit;
}

require_once 'config.php';

$userId   = (int) $_SESSION['user_id'];
$username = (string) $_SESSION['username'];

// All other registered users (for challenge form)
$stmt = $pdo->prepare('SELECT id, username FROM users WHERE id != ? ORDER BY username');
$stmt->execute([$userId]);
$otherUsers = $stmt->fetchAll();

// Challenges sent TO the current user that are still pending
$stmt = $pdo->prepare('
    SELECT c.id, u.username AS challenger_name, c.created_at
    FROM challenges c
    JOIN users u ON u.id = c.challenger_id
    WHERE c.challenged_id = ? AND c.status = \'pending\'
    ORDER BY c.created_at DESC
');
$stmt->execute([$userId]);
$incoming = $stmt->fetchAll();

// Active games involving the current user
$stmt = $pdo->prepare('
    SELECT g.id,
           ux.username AS player_x_name,
           uo.username AS player_o_name,
           g.current_turn,
           g.updated_at
    FROM games g
    JOIN users ux ON ux.id = g.player_x_id
    JOIN users uo ON uo.id = g.player_o_id
    WHERE (g.player_x_id = ? OR g.player_o_id = ?) AND g.status = \'active\'
    ORDER BY g.updated_at DESC
');
$stmt->execute([$userId, $userId]);
$activeGames = $stmt->fetchAll();

// Completed games (history)
$stmt = $pdo->prepare('
    SELECT g.id,
           ux.username AS player_x_name,
           uo.username AS player_o_name,
           g.status,
           uw.username AS winner_name,
           g.updated_at
    FROM games g
    JOIN users ux ON ux.id = g.player_x_id
    JOIN users uo ON uo.id = g.player_o_id
    LEFT JOIN users uw ON uw.id = g.winner_id
    WHERE (g.player_x_id = ? OR g.player_o_id = ?) AND g.status != \'active\'
    ORDER BY g.updated_at DESC
    LIMIT 20
');
$stmt->execute([$userId, $userId]);
$history = $stmt->fetchAll();

$msg = isset($_GET['msg']) ? htmlspecialchars((string) $_GET['msg'], ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lobby – Tic-Tac-Toe</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <header>
        <h1>Tic-Tac-Toe Lobby</h1>
        <span>Logged in as <strong><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></strong></span>
        <a class="btn btn-sm btn-outline" href="logout.php">Log Out</a>
    </header>

    <?php if ($msg !== ''): ?>
    <p class="flash"><?= $msg ?></p>
    <?php endif; ?>

    <!-- ── Challenge a player ── -->
    <section>
        <h2>Challenge a Player</h2>
        <?php if (empty($otherUsers)): ?>
        <p class="muted">No other players registered yet.</p>
        <?php else: ?>
        <div class="form-block">
            <form method="post" action="challenge.php">
                <select name="challenged_id" required>
                    <option value="">— select opponent —</option>
                    <?php foreach ($otherUsers as $u): ?>
                    <option value="<?= $u['id'] ?>">
                        <?= htmlspecialchars((string) $u['username'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn" type="submit" name="action" value="send">Send Challenge</button>
            </form>
        </div>
        <?php endif; ?>
    </section>

    <!-- ── Incoming challenges ── -->
    <section>
        <h2>Incoming Challenges</h2>
        <?php if (empty($incoming)): ?>
        <p class="muted">No pending challenges.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr><th>From</th><th>Received</th><th>Action</th></tr>
            </thead>
            <tbody>
            <?php foreach ($incoming as $c): ?>
            <tr>
                <td><?= htmlspecialchars((string) $c['challenger_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $c['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <form method="post" action="challenge.php">
                        <input type="hidden" name="challenge_id" value="<?= (int) $c['id'] ?>">
                        <button class="btn btn-green btn-sm" name="action" value="accept">Accept</button>
                    </form>
                    <form method="post" action="challenge.php">
                        <input type="hidden" name="challenge_id" value="<?= (int) $c['id'] ?>">
                        <button class="btn btn-red btn-sm" name="action" value="decline">Decline</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <!-- ── Active games ── -->
    <section>
        <h2>Active Games</h2>
        <?php if (empty($activeGames)): ?>
        <p class="muted">No active games.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr><th>X (first)</th><th>O (second)</th><th>Whose Turn</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($activeGames as $g): ?>
            <?php
                $turnName = $g['current_turn'] === 'X'
                    ? htmlspecialchars((string) $g['player_x_name'], ENT_QUOTES, 'UTF-8')
                    : htmlspecialchars((string) $g['player_o_name'], ENT_QUOTES, 'UTF-8');
            ?>
            <tr>
                <td><?= htmlspecialchars((string) $g['player_x_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $g['player_o_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $turnName ?></td>
                <td><a class="btn btn-sm" href="game.php?id=<?= (int) $g['id'] ?>">Play</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>

    <!-- ── Game history ── -->
    <section>
        <h2>Game History</h2>
        <?php if (empty($history)): ?>
        <p class="muted">No completed games yet.</p>
        <?php else: ?>
        <table>
            <thead>
                <tr><th>X (first)</th><th>O (second)</th><th>Result</th><th>Date</th></tr>
            </thead>
            <tbody>
            <?php foreach ($history as $g): ?>
            <tr>
                <td><?= htmlspecialchars((string) $g['player_x_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $g['player_o_name'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                <?php if ($g['status'] === 'draw'): ?>
                    Draw
                <?php else: ?>
                    <?= htmlspecialchars((string) ($g['winner_name'] ?? '?'), ENT_QUOTES, 'UTF-8') ?> won
                <?php endif; ?>
                </td>
                <td><?= htmlspecialchars((string) $g['updated_at'], ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </section>
</div>
</body>
</html>
