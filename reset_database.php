<?php
require_once 'db_connect.php';

// ONLY USE THIS FOR DEVELOPMENT - IT DELETES EVERYTHING!

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Delete all data
    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE fixtures");
    $conn->query("TRUNCATE TABLE players");
    $conn->query("TRUNCATE TABLE teams");
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");
    
    // Re-insert sample data
    $conn->query("INSERT INTO teams (name, city, coach, founded_year) VALUES
        ('Manchester United', 'Manchester', 'Erik ten Hag', 1878),
        ('Liverpool FC', 'Liverpool', 'Jurgen Klopp', 1892),
        ('Arsenal FC', 'London', 'Mikel Arteta', 1886),
        ('Chelsea FC', 'London', 'Mauricio Pochettino', 1905)");
    
    header("Location: teams.php?reset=success");
    exit();
} else {
    header("Location: teams.php");
    exit();
}
?>