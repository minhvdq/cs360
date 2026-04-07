<?php
declare(strict_types=1);

session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?error=not_logged_in');
    exit;
}

$username = $_SESSION['username'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
</head>
<body>
<div class="container">
    <h1>Dashboard</h1>
    <p>Welcome, <strong><?php echo htmlspecialchars((string) $username, ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
    <p>You are logged in.</p>
    <div class="actions">
        <a class="btn" href="logout.php">Log Out</a>
    </div>
</div>
</body>
</html>
