<?php
// Simple script to fix the database issue with missing 'name' column

// Output directly to the console
echo "Starting database fix script...\n";

// Database connection parameters - adjust these as needed
$host = 'localhost';
$dbname = 'chat_symfony';
$username = 'root';
$password = '';

try {
    // Connect to database
    echo "Connecting to database...\n";
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    echo "Connected successfully.\n";
    
    // Check if we're actually connecting to the right database
    $result = $pdo->query("SELECT DATABASE()")->fetchColumn();
    echo "Current database: " . $result . "\n";
    
    // List all tables
    echo "Tables in the database:\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        echo "- $table\n";
    }
    
    // Check the structure of the question table
    if (in_array('question', $tables)) {
        echo "\nColumns in question table:\n";
        $columns = $pdo->query("DESCRIBE question")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($columns as $column) {
            echo "- $column\n";
        }
        
        // Add the name column if it doesn't exist
        if (!in_array('name', $columns)) {
            echo "\nAdding 'name' column to question table...\n";
            $pdo->exec("ALTER TABLE question ADD COLUMN name VARCHAR(100) NOT NULL DEFAULT 'Default Name'");
            echo "Column 'name' added.\n";
        } else {
            echo "\nColumn 'name' already exists in question table.\n";
        }
    } else {
        echo "\nWarning: question table not found!\n";
    }
    
    echo "\nScript completed.\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "\n";
}
