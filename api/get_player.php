<?php
require_once '../db_connect.php';
$id = $_GET['id'];
$result = $conn->query("SELECT * FROM players WHERE id=$id");
echo json_encode($result->fetch_assoc());
?>