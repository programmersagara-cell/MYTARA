<?php
/**
 * Migration: Create messages table for the messenger
 * 
 * Run: php database/migrations/006_create_messages_table.php
 */

$pdo = new PDO('mysql:host=127.0.0.1;dbname=itassets;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Running migration: Create messages table...\n";

// Check if messages table exists
$stmt = $pdo->query("SHOW TABLES LIKE 'messages'");
if ($stmt->fetch()) {
    echo "  - 'messages' table already exists.\n";
} else {
    $pdo->exec("
        CREATE TABLE messages (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sender_id INT UNSIGNED NOT NULL,
            receiver_id INT UNSIGNED NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
            FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE,
            INDEX idx_messages_sender (sender_id),
            INDEX idx_messages_receiver (receiver_id),
            INDEX idx_messages_read (receiver_id, is_read),
            INDEX idx_messages_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "  ✓ Created 'messages' table.\n";
}

echo "\nMigration completed successfully.\n";

