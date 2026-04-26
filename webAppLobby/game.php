<?php
declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=not_logged_in');
    exit;
}

require_once 'config.php';

$userId = (int) $_SESSION['user_id'];
$gameId = (int) ($_GET['id'] ?? 0);

if ($gameId <= 0) {
    header('Location: lobby.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT g.*,
           ux.username AS player_x_name,
           uo.username AS player_o_name,
           uw.username AS winner_name
    FROM games g
    JOIN users ux ON ux.id = g.player_x_id
    JOIN users uo ON uo.id = g.player_o_id
    LEFT JOIN users uw ON uw.id = g.winner_id
    WHERE g.id = ?
');
$stmt->execute([$gameId]);
$game = $stmt->fetch();

if (!$game) {
    header('Location: lobby.php?msg=Game+not+found.');
    exit;
}

$isPlayerX = ($userId === (int) $game['player_x_id']);
$isPlayerO = ($userId === (int) $game['player_o_id']);

if (!$isPlayerX && !$isPlayerO) {
    header('Location: lobby.php?msg=You+are+not+a+participant+in+that+game.');
    exit;
}

$mySymbol = $isPlayerX ? 'X' : 'O';
$isMyTurn = ($game['status'] === 'active' && $game['current_turn'] === $mySymbol);

// ── Handle a move ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $isMyTurn) {
    $pos = (int) ($_POST['pos'] ?? -1);

    if ($pos >= 0 && $pos <= 8) {
        $board = (string) $game['board'];

        if ($board[$pos] === '-') {
            $board[$pos] = $mySymbol;

            // Check win (all eight lines)
            $lines  = [[0,1,2],[3,4,5],[6,7,8],[0,3,6],[1,4,7],[2,5,8],[0,4,8],[2,4,6]];
            $winner = null;
            foreach ($lines as [$a, $b, $c]) {
                if ($board[$a] !== '-' && $board[$a] === $board[$b] && $board[$b] === $board[$c]) {
                    $winner = $mySymbol;
                    break;
                }
            }
            $isDraw = ($winner === null && strpos($board, '-') === false);

            if ($winner !== null) {
                $stmt = $pdo->prepare(
                    "UPDATE games SET board = ?, status = 'completed', winner_id = ?, updated_at = NOW() WHERE id = ?"
                );
                $stmt->execute([$board, $userId, $gameId]);
            } elseif ($isDraw) {
                $stmt = $pdo->prepare(
                    "UPDATE games SET board = ?, status = 'draw', updated_at = NOW() WHERE id = ?"
                );
                $stmt->execute([$board, $gameId]);
            } else {
                $nextTurn = ($mySymbol === 'X') ? 'O' : 'X';
                $stmt = $pdo->prepare(
                    'UPDATE games SET board = ?, current_turn = ?, updated_at = NOW() WHERE id = ?'
                );
                $stmt->execute([$board, $nextTurn, $gameId]);
            }

            header("Location: game.php?id={$gameId}");
            exit;
        }
    }
}

$cells = str_split((string) $game['board']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game #<?= $gameId ?> – Tic-Tac-Toe</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <header>
        <h1>Game #<?= $gameId ?></h1>
        <a class="btn btn-sm btn-outline" href="lobby.php">← Lobby</a>
    </header>

    <div class="game-meta">
        <p><strong>X:</strong> <?= htmlspecialchars((string) $game['player_x_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><strong>O:</strong> <?= htmlspecialchars((string) $game['player_o_name'], ENT_QUOTES, 'UTF-8') ?></p>
        <p>You are <strong><?= $mySymbol ?></strong></p>
    </div>

    <?php if ($game['status'] === 'completed'): ?>
        <p class="flash flash-green">
            <?= htmlspecialchars((string) $game['winner_name'], ENT_QUOTES, 'UTF-8') ?> wins!
        </p>
    <?php elseif ($game['status'] === 'draw'): ?>
        <p class="flash">It's a draw!</p>
    <?php elseif ($isMyTurn): ?>
        <p class="flash flash-blue">Your turn — click a square.</p>
    <?php else: ?>
        <p class="muted" style="margin-bottom:1rem">Waiting for opponent's move&hellip;</p>
    <?php endif; ?>

    <!-- Board: 3×3 grid -->
    <div class="board">
        <?php foreach ($cells as $i => $cell): ?>
            <?php if ($cell === '-' && $isMyTurn): ?>
                <form method="post" style="display:contents">
                    <input type="hidden" name="pos" value="<?= $i ?>">
                    <button class="cell cell-empty" type="submit" aria-label="Place <?= $mySymbol ?> at position <?= $i ?>"></button>
                </form>
            <?php elseif ($cell === 'X'): ?>
                <div class="cell cell-x">X</div>
            <?php elseif ($cell === 'O'): ?>
                <div class="cell cell-o">O</div>
            <?php else: ?>
                <div class="cell"></div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php if ($game['status'] !== 'active'): ?>
        <p style="text-align:center">
            <a class="btn" href="lobby.php">Back to Lobby</a>
        </p>
    <?php endif; ?>
</div>
</body>
</html>
