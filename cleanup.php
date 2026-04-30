<?php
require_once 'db_connect.php';

// This page helps clean up data before deleting a team
// Access at: http://localhost/football_system/cleanup.php

$message = '';
$error = '';

// Handle player deletion
if (isset($_POST['delete_players']) && isset($_POST['team_id'])) {
    $team_id = $_POST['team_id'];
    $stmt = $conn->prepare("DELETE FROM players WHERE team_id = ?");
    $stmt->bind_param("i", $team_id);
    if ($stmt->execute()) {
        $message = "Players deleted successfully!";
    } else {
        $error = "Error deleting players: " . $conn->error;
    }
}

// Handle fixture deletion
if (isset($_POST['delete_fixtures']) && isset($_POST['team_id'])) {
    $team_id = $_POST['team_id'];
    $stmt = $conn->prepare("DELETE FROM fixtures WHERE home_team_id = ? OR away_team_id = ?");
    $stmt->bind_param("ii", $team_id, $team_id);
    if ($stmt->execute()) {
        $message = "Fixtures deleted successfully!";
    } else {
        $error = "Error deleting fixtures: " . $conn->error;
    }
}

// Get teams with related data
$teams_with_data = $conn->query("
    SELECT 
        t.id,
        t.name,
        (SELECT COUNT(*) FROM players WHERE team_id = t.id) as player_count,
        (SELECT COUNT(*) FROM fixtures WHERE home_team_id = t.id OR away_team_id = t.id) as fixture_count
    FROM teams t
    HAVING player_count > 0 OR fixture_count > 0
");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cleanup Helper - Football League</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <div class="sidebar-header"><h2>⚽ Football League</h2></div>
            <ul class="sidebar-menu">
                <li><a href="index.php">📊 Dashboard</a></li>
                <li><a href="teams.php">🏆 Teams</a></li>
                <li><a href="players.php">👥 Players</a></li>
                <li><a href="fixtures.php">📅 Fixtures</a></li>
                <li><a href="results.php">📝 Results</a></li>
                <li><a href="standings.php">📊 Standings</a></li>
                <li><a href="cleanup.php" class="active">🧹 Cleanup</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header>
                <h1>Database Cleanup Helper</h1>
            </header>

            <?php if($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="results-form">
                <h2>Teams with Related Data</h2>
                <p>These teams cannot be deleted until their related data (players and fixtures) are removed.</p>
                
                <?php if($teams_with_data->num_rows == 0): ?>
                    <div class="no-data">No teams have related data. All teams can be deleted safely.</div>
                <?php else: ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Team</th>
                                <th>Players</th>
                                <th>Fixtures</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($team = $teams_with_data->fetch_assoc()): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($team['name']); ?></strong></td>
                                <td><?php echo $team['player_count']; ?> player(s)</td>
                                <td><?php echo $team['fixture_count']; ?> fixture(s)</td>
                                <td>
                                    <form method="POST" style="display: inline-block;">
                                        <input type="hidden" name="team_id" value="<?php echo $team['id']; ?>">
                                        <?php if($team['player_count'] > 0): ?>
                                        <button type="submit" name="delete_players" class="btn-warning" onclick="return confirm('Delete all players from <?php echo addslashes($team['name']); ?>?')">
                                            Delete Players
                                        </button>
                                        <?php endif; ?>
                                        <?php if($team['fixture_count'] > 0): ?>
                                        <button type="submit" name="delete_fixtures" class="btn-warning" onclick="return confirm('Delete all fixtures involving <?php echo addslashes($team['name']); ?>?')">
                                            Delete Fixtures
                                        </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <div class="results-form">
                <h2>How to Delete a Team</h2>
                <ol style="margin-left: 20px; line-height: 1.8;">
                    <li>Go to <strong>Players</strong> page and delete all players from that team</li>
                    <li>Go to <strong>Fixtures</strong> page and delete all fixtures involving that team</li>
                    <li>Return to <strong>Teams</strong> page and delete the team</li>
                    <li>Or use the cleanup buttons above to quickly remove related data</li>
                </ol>
            </div>

            <div class="results-form">
                <h2>Alternative: Delete Everything (Reset Database)</h2>
                <p>⚠️ Warning: This will delete ALL data from your database!</p>
                <form method="POST" action="reset_database.php" onsubmit="return confirm('ARE YOU ABSOLUTELY SURE? This will delete ALL teams, players, and fixtures!');">
                    <button type="submit" class="btn-delete" style="background: #dc3545;">Reset Entire Database</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>