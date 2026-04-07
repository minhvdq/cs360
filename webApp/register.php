<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        header('Location: register.php?error=missing_fields');
        exit;
    }

    try {
        $checkStmt = $pdo->prepare('SELECT id FROM users WHERE username = ?');
        $checkStmt->execute([$username]);

        if ($checkStmt->fetch()) {
            header('Location: register.php?error=username_taken');
            exit;
        }

        $insertStmt = $pdo->prepare('
            INSERT INTO users (username, password_hash)
            VALUES (?, SHA2(?, 256))
        ');
        $insertStmt->execute([$username, $password]);

        header('Location: login.php?success=registered');
        exit;
    } catch (PDOException $e) {
        header('Location: register.php?error=server_error');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
</head>
<body>
<div class="container">
    <h1>Create Account</h1>

    <?php if ($error === 'missing_fields'): ?>
        <div class="message error">Please fill in both username and password.</div>
    <?php elseif ($error === 'username_taken'): ?>
        <div class="message error">That username is already in use.</div>
    <?php elseif ($error === 'server_error'): ?>
        <div class="message error">Unable to register right now. Please try again.</div>
    <?php elseif ($success === 'registered'): ?>
        <div class="message success">Registration successful. You can now log in.</div>
    <?php endif; ?>

    <form method="post" action="register.php">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" maxlength="50" required>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>

        <button type="submit">Register</button>
    </form>

    <div class="links">
        <a href="login.php">Already have an account? Log in</a>
    </div>
</div>
</body>
</html>
