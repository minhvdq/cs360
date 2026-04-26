<?php
declare(strict_types=1);

$dbName = 's26_vudimi01';
$dbUser = 'vudimi01';
$dbPass = 'vudimi01';

// Use PostgreSQL locally; MySQL on the school server (cray)
$onSchoolServer = in_array(gethostname(), ['cray', 'cray.ms.gettysburg.edu'], true);

$dsn = $onSchoolServer
    ? "mysql:host=cray;dbname={$dbName};charset=utf8mb4"
    : "pgsql:host=127.0.0.1;dbname={$dbName}";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    die('Database connection failed. Please check your settings.');
}
