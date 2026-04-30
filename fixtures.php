<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['schedule'])) {
    $match_date = $_POST['match_date'];
    $match_time = $_POST['match_time'];
    $home_team_id = $_POST['home_team_id'];
    $away_team_id = $_POST['away_team_id'];
    $venue = $_POST['venue'];
    $round = $_POST['Matchday'];
    
    $stmt = $conn->prepare("INSERT INTO fixtures (match_date, match_time, home_team_id, away_team_id, venue, round, status) VALUES (?, ?, ?, ?, ?, ?, 'scheduled')");
    $stmt->bind_param("ssiisi", $match_date, $match_time, $home_team_id, $away_team_id, $venue, $round);
    $stmt->execute();
    header("Location: fixtures.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fixtures - Football League</title>
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
                <li><a href="fixtures.php" class="active">📅 Fixtures</a></li>
                <li><a href="results.php">📝 Results</a></li>
                <li><a href="standings.php">📊 Standings</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header>
                <h1>Match Fixtures</h1>
                <button class="btn-primary" onclick="openFixtureModal()">+ Schedule Match</button>
            </header>

            <div class="fixtures-list">
                <?php
                $fixtures = $conn->query("
                    SELECT f.*, h.name as home_name, a.name as away_name 
                    FROM fixtures f
                    JOIN teams h ON f.home_team_id = h.id
                    JOIN teams a ON f.away_team_id = a.id
                    ORDER BY f.match_date ASC, f.match_time ASC
                ");
                if($fixtures->num_rows == 0): ?>
                    <div class="no-data">No fixtures scheduled. Click "Schedule Match" to create fixtures.</div>
                <?php else:
                while($fixture = $fixtures->fetch_assoc()): ?>
                <div class="fixture-card">
                    <div class="fixture-date">
                        📅 <?php echo date('F j, Y', strtotime($fixture['match_date'])); ?> 
                        | ⏰ <?php echo date('g:i A', strtotime($fixture['match_time'])); ?>
                        <?php if($fixture['venue']): ?> | 📍 <?php echo $fixture['venue']; endif; ?>
                    </div>
                    <div class="fixture-teams">
                        <span class="team"><?php echo $fixture['home_name']; ?></span>
                        <span class="vs">VS</span>
                        <span class="team"><?php echo $fixture['away_name']; ?></span>
                    </div>
                    <?php if($fixture['status'] == 'played'): ?>
                        <div class="fixture-result">Result: <strong><?php echo $fixture['home_score']; ?> - <?php echo $fixture['away_score']; ?></strong></div>
                    <?php endif; ?>
                    <div class="fixture-status">
                        <span class="status-badge <?php echo $fixture['status']; ?>"><?php echo ucfirst($fixture['status']); ?></span>
                        <?php if($fixture['round']): ?><span class="round-badge">Round <?php echo $fixture['round']; ?></span><?php endif; ?>
                    </div>
                </div>
                <?php endwhile; endif; ?>
            </div>
        </main>
    </div>

    <div id="fixtureModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeFixtureModal()">&times;</span>
            <h2>Schedule Match</h2>
            <form method="POST">
                <input type="hidden" name="schedule" value="1">
                <div class="form-group">
                    <label>Date *</label>
                    <input type="date" name="match_date" required>
                </div>
                <div class="form-group">
                    <label>Time *</label>
                    <input type="time" name="match_time" required>
                </div>
                <div class="form-group">
                    <label>Home Team *</label>
                    <select name="home_team_id" required>
                        <option value="">Select Home Team</option>
                        <?php
                        $teams = $conn->query("SELECT id, name FROM teams ORDER BY name");
                        while($team = $teams->fetch_assoc()):
                        ?>
                        <option value="<?php echo $team['id']; ?>"><?php echo $team['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Away Team *</label>
                    <select name="away_team_id" required>
                        <option value="">Select Away Team</option>
                        <?php
                        $teams = $conn->query("SELECT id, name FROM teams ORDER BY name");
                        while($team = $teams->fetch_assoc()):
                        ?>
                        <option value="<?php echo $team['id']; ?>"><?php echo $team['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Venue</label>
                    <input type="text" name="venue" placeholder="Stadium name">
                </div>
                <div class="form-group">
                    <label>Matchday</label>
                    <input type="number" name="round">
                </div>
                <button type="submit" class="btn-primary">Schedule</button>
            </form>
        </div>
    </div>

    <script>
        function openFixtureModal() { document.getElementById('fixtureModal').style.display = 'block'; }
        function closeFixtureModal() { document.getElementById('fixtureModal').style.display = 'none'; }
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) event.target.style.display = 'none';
        }
    </script>
</body>
</html>