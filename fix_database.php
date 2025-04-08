<?php

// Create a log file where we can track what's happening
$logFile = __DIR__ . '/db_fix_log.txt';
file_put_contents($logFile, "Starting database fix script at " . date('Y-m-d H:i:s') . "\n");

function log_message($message) {
    global $logFile;
    $message = date('H:i:s') . " - $message\n";
    file_put_contents($logFile, $message, FILE_APPEND);
    echo $message;
}

// Database connection parameters - adjust these to match your actual configuration
$host = 'localhost';
$dbname = 'chat_symfony'; // Adjust if your database has a different name
$username = 'root'; // Adjust to your database username
$password = ''; // Adjust to your database password

try {
    log_message("Connecting to database $dbname at $host...");
    
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    log_message("Connected to database successfully");
    
    // Start transaction to ensure all or nothing
    $pdo->beginTransaction();
    log_message("Started transaction");
    
    try {
        // First check if the conflicting foreign keys exist
        log_message("Checking for existing foreign keys on message table");
        $checkForeignKeys = $pdo->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_TYPE = 'FOREIGN KEY' 
            AND TABLE_NAME = 'message' 
            AND TABLE_SCHEMA = '$dbname'
        ");
        
        $constraints = $checkForeignKeys->fetchAll();
        log_message("Found " . count($constraints) . " foreign key constraints");
        
        $constraintNames = array_column($constraints, 'CONSTRAINT_NAME');
        
        // Drop all foreign keys on message table to avoid conflicts
        foreach ($constraintNames as $constraintName) {
            log_message("Dropping foreign key constraint: $constraintName");
            $pdo->exec("ALTER TABLE message DROP FOREIGN KEY `$constraintName`");
        }
        
        // Check if we need to modify the question table
        log_message("Analyzing structure of question table");
        $questionColumns = $pdo->query("SHOW COLUMNS FROM question")->fetchAll();
        $questionColumnNames = array_column($questionColumns, 'Field');
        log_message("Found columns in question table: " . implode(', ', $questionColumnNames));
        
        if (!in_array('name', $questionColumnNames)) {
            log_message("Adding 'name' column to question table");
            $pdo->exec("ALTER TABLE question ADD COLUMN name VARCHAR(100) NOT NULL");
        }
        
        if (!in_array('email', $questionColumnNames)) {
            log_message("Adding 'email' column to question table");
            $pdo->exec("ALTER TABLE question ADD COLUMN email VARCHAR(100) NOT NULL, ADD UNIQUE INDEX UNIQ_B6F7494EE7927C74 (email)");
        }
        
        // If question_id exists but id doesn't, rename it
        if (in_array('question_id', $questionColumnNames) && !in_array('id', $questionColumnNames)) {
            log_message("Renaming question_id to id in question table");
            $pdo->exec("ALTER TABLE question CHANGE question_id id INT AUTO_INCREMENT NOT NULL");
        }
        
        // Check if we need to modify the reponse table
        log_message("Analyzing structure of reponse table");
        $reponseColumns = $pdo->query("SHOW COLUMNS FROM reponse")->fetchAll();
        $reponseColumnNames = array_column($reponseColumns, 'Field');
        log_message("Found columns in reponse table: " . implode(', ', $reponseColumnNames));
        
        if (!in_array('username', $reponseColumnNames)) {
            log_message("Adding 'username' column to reponse table");
            $pdo->exec("ALTER TABLE reponse ADD COLUMN username VARCHAR(100) NOT NULL");
        }
        
        if (!in_array('role', $reponseColumnNames)) {
            log_message("Adding 'role' column to reponse table");
            $pdo->exec("ALTER TABLE reponse ADD COLUMN role VARCHAR(100) NOT NULL");
        }
        
        // If reponse_id exists but id doesn't, rename it
        if (in_array('reponse_id', $reponseColumnNames) && !in_array('id', $reponseColumnNames)) {
            log_message("Renaming reponse_id to id in reponse table");
            $pdo->exec("ALTER TABLE reponse CHANGE reponse_id id INT AUTO_INCREMENT NOT NULL");
        }
        
        // Re-add the correct foreign keys on message table
        log_message("Adding correct foreign keys to message table");
        $pdo->exec("ALTER TABLE message ADD CONSTRAINT FK_B6BD307F1E27F6BF FOREIGN KEY (question_id) REFERENCES question (id)");
        $pdo->exec("ALTER TABLE message ADD CONSTRAINT FK_B6BD307FCF18BB82 FOREIGN KEY (reponse_id) REFERENCES reponse (id)");
        
        // Commit all changes
        $pdo->commit();
        log_message("SUCCESS: All database changes applied successfully!");
        
    } catch (Exception $e) {
        // Roll back the transaction if something failed
        $pdo->rollBack();
        log_message("ERROR: Database update failed: " . $e->getMessage());
    }
    
} catch (PDOException $e) {
    log_message("ERROR: Connection failed: " . $e->getMessage());
}
