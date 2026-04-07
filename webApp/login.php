<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/config.php';

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        header('Location: login.php?error=missing_fields');
        exit;
    }

    try {
        $stmt = $pdo->prepare('
            SELECT id, username
            FROM users
            WHERE username = :username
              AND password_hash = SHA2(:password, 256)
            LIMIT 1
        ');
        $stmt->execute([
            'username' => $username,
            'password' => $password,
        ]);

        $user = $stmt->fetch();

        if ($user) {
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['username'] = $user['username'];
            header('Location: dashboard.php');
            exit;
        }

        header('Location: login.php?error=invalid_credentials');
        exit;
    } catch (PDOException $e) {
        header('Location: login.php?error=server_error');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
</head>
<body>
<div class="container">
    <h1>Log In</h1>
    <p>Welcome to the login page.</p>
    <?php if ($error === 'invalid_credentials'): ?>
        <div class="message error">Invalid Username/Password.</div>
    <?php elseif ($error === 'missing_fields'): ?>
        <div class="message error">Please fill in both username and password.</div>
    <?php elseif ($error === 'not_logged_in'): ?>
        <div class="message error">Please log in to access the dashboard.</div>
    <?php elseif ($error === 'server_error'): ?>
        <div class="message error">Unable to log in right now. Please try again.</div>
    <?php elseif ($success === 'registered'): ?>
        <div class="message success">Account created. Please log in.</div>
    <?php elseif ($success === 'logged_out'): ?>
        <div class="message success">You have been logged out.</div>
    <?php endif; ?>

    <form method="post" action="login.php">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" maxlength="50" required>

        <label for="password">Password</label>
        <input id="password" name="password" type="password" required>

        <button type="submit">Log In</button>
    </form>

    <div class="links">
        <a href="register.php">Need an account? Register</a>
    </div>
</div>
</body>
</html>
