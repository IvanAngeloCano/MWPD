<?php
// Database setup script for Job Fair module
require_once 'connection.php';

try {
    // Read the SQL file contents
    $sql_content = file_get_contents('job_fair_tables.sql');
    
    // Split into separate SQL statements
    $statements = array_filter(
        array_map(
            'trim',
            explode(';', $sql_content)
        ),
        function($statement) {
            return !empty($statement);
        }
    );
    
    // Execute each SQL statement
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);
    
    echo "<h1>Setting up Job Fair Tables</h1>";
    
    foreach ($statements as $index => $statement) {
        try {
            $pdo->exec($statement);
            echo "<p>✅ Successfully executed statement " . ($index + 1) . "</p>";
        } catch (PDOException $e) {
            echo "<p>❌ Error in statement " . ($index + 1) . ": " . $e->getMessage() . "</p>";
            // Continue with other statements even if this one fails
        }
    }
    
    echo "<h2>Database setup completed</h2>";
    echo "<p>You can now <a href='job_fairs.php'>go to the Job Fairs module</a>.</p>";
    
} catch (Exception $e) {
    echo "<h1>Setup Error</h1>";
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?> 