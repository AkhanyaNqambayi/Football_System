<?php
require_once '../db_connect.php';
$team_id = $_GET['team_id'] ?? '';
$sql = "SELECT p.*, t.name as team_name FROM players p JOIN teams t ON p.team_id = t.id";
if($team_id) $sql .= " WHERE p.team_id = $team_id";
$sql .= " ORDER BY t.name, p.name";
$result = $conn->query($sql);
$players = [];
while($row = $result->fetch_assoc()) $players[] = $row;
echo json_encode($players);
?>