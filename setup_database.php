<?php
// Get database configuration from .env
$env = file_get_contents(__DIR__ . '/.env');
preg_match('/DATABASE_URL=(.+)/', $env, $matches);

if (empty($matches[1])) {
    die("Could not find DATABASE_URL in .env file\n");
}

$dbUrl = $matches[1];

// Parse the database URL
preg_match('/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)\?(.*)/', $dbUrl, $parts);

if (count($parts) < 6) {
    die("Invalid DATABASE_URL format\n");
}

$user = $parts[1];
$pass = $parts[2];
$host = $parts[3];
$port = $parts[4];
$dbname = $parts[5];

// Connect to the database
try {
    $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
    echo "Database $dbname created or already exists.\n";
    
    // Switch to the database
    $pdo->exec("USE $dbname");
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/migrations/create_schema.sql');
    $pdo->exec($sql);
    
    echo "Database schema created successfully!\n";
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage() . "\n");
}
