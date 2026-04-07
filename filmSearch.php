<?php
declare(strict_types=1);

/*
 * Loads key=value pairs from .env in this folder.
 */
function loadEnvFile(string $path): array
{
    if (!is_file($path)) {
        return [];
    }

    $values = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return [];
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);
        $value = trim($value, "\"'");
        $values[$key] = $value;
    }

    return $values;
}

$env = loadEnvFile(__DIR__ . '/.env');

$dbHost = $env['DB_HOST'] ? "crayon:3306";
$dbName = $env['DB_NAME'] ? "sakila";
$dbUser = $env['DB_USER'] ? "vudimi01";
$dbPass = $env['DB_PASS'] ? "vudimi01";
$dbCharset = $env['DB_CHARSET'] ? "utf8mb4";

$title = isset($_GET['title']) ? trim((string) $_GET['title']) : '';
$rating = isset($_GET['rating']) ? trim((string) $_GET['rating']) : '';

$films = [];
$errorMessage = '';

if ($title !== '' || $rating !== '') {
    try {
        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";
        $pdo = new PDO($dsn, $dbUser, $dbPass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        $sql = "SELECT title, rating, release_year
                FROM film
                WHERE (:titleExact = '' OR title LIKE :titleLike)
                  AND (:rating = '' OR rating = :rating)
                ORDER BY title ASC";

        $titleLike = '%' . $title . '%';
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':titleExact', $title, PDO::PARAM_STR);
        $stmt->bindParam(':titleLike', $titleLike, PDO::PARAM_STR);
        $stmt->bindParam(':rating', $rating, PDO::PARAM_STR);
        $stmt->execute();
        $films = $stmt->fetchAll();
    } catch (PDOException $e) {
        $errorMessage = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Film Search</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2rem; }
        form { margin-bottom: 1.25rem; }
        label { display: inline-block; width: 90px; margin-bottom: 0.5rem; }
        input, select { padding: 0.4rem; width: 220px; }
        button { padding: 0.45rem 0.8rem; margin-top: 0.5rem; }
        table { border-collapse: collapse; width: 100%; margin-top: 1rem; }
        th, td { border: 1px solid #ddd; padding: 0.55rem; text-align: left; }
        th { background: #f2f2f2; }
        .message { margin-top: 1rem; }
        .error { color: #b00020; }
    </style>
</head>
<body>
    <h1>Film Search</h1>

    <form method="get" action="filmSearch.php">
        <div>
            <label for="title">Title:</label>
            <input
                type="text"
                id="title"
                name="title"
                value="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
                placeholder="Enter full or partial title"
            >
        </div>
        <div>
            <label for="rating">Rating:</label>
            <select id="rating" name="rating">
                <option value="">Any rating</option>
                <?php
                $ratings = ['G', 'PG', 'PG-13', 'R', 'NC-17'];
                foreach ($ratings as $r) {
                    $selected = ($rating === $r) ? 'selected' : '';
                    echo '<option value="' . htmlspecialchars($r, ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . htmlspecialchars($r, ENT_QUOTES, 'UTF-8') . '</option>';
                }
                ?>
            </select>
        </div>
        <button type="submit">Search</button>
    </form>

    <?php if ($errorMessage !== ''): ?>
        <p class="message error"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></p>
    <?php elseif ($title !== '' || $rating !== ''): ?>
        <?php if (count($films) > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Rating</th>
                        <th>Release Year</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($films as $film): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) ($film['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($film['rating'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($film['release_year'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="message">No films matched your search.</p>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>
