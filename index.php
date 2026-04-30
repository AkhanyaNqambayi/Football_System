<?php
require_once 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Football League Manager</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <div class="sidebar-header">
                <h2>⚽ Football League</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="index.php" class="active">📊 Dashboard</a></li>
                <li><a href="teams.php">🏆 Teams</a></li>
                <li><a href="players.php">👥 Players</a></li>
                <li><a href="fixtures.php">📅 Fixtures</a></li>
                <li><a href="results.php">📝 Results</a></li>
                <li><a href="standings.php">📊 Standings</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header>
                <h1>Dashboard</h1>
                <div class="user-info">Welcome to Football League Manager</div>
            </header>

            <div class="stats-grid">
                <?php
                $teams = $conn->query("SELECT COUNT(*) as count FROM teams")->fetch_assoc();
                $players = $conn->query("SELECT COUNT(*) as count FROM players")->fetch_assoc();
                $matches = $conn->query("SELECT COUNT(*) as count FROM fixtures")->fetch_assoc();
                $played = $conn->query("SELECT COUNT(*) as count FROM fixtures WHERE status = 'played'")->fetch_assoc();
                $upcoming = $conn->query("SELECT COUNT(*) as count FROM fixtures WHERE status = 'scheduled' AND match_date >= CURDATE()")->fetch_assoc();
                ?>
                <div class="stat-card">
                    <div class="stat-icon">🏆</div>
                    <div class="stat-info">
                        <h3><?php echo $teams['count']; ?></h3>
                        <p>Teams</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <h3><?php echo $players['count']; ?></h3>
                        <p>Players</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">⚽</div>
                    <div class="stat-info">
                        <h3><?php echo $matches['count']; ?></h3>
                        <p>Total Fixtures</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-info">
                        <h3><?php echo $played['count']; ?></h3>
                        <p>Matches Played</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📅</div>
                    <div class="stat-info">
                        <h3><?php echo $upcoming['count']; ?></h3>
                        <p>Upcoming</p>
                    </div>
                </div>
            </div>

            <div class="recent-matches">
                <h2>Recent Matches</h2>
                <table class="data-table">
                    <thead>
                        <tr><th>Date</th><th>Home</th><th>Score</th><th>Away</th>比
                    </thead>
                    <tbody>
                        <?php
                        $recent = $conn->query("
                            SELECT f.*, h.name as home_name, a.name as away_name 
                            FROM fixtures f
                            JOIN teams h ON f.home_team_id = h.id
                            JOIN teams a ON f.away_team_id = a.id
                            WHERE f.status = 'played'
                            ORDER BY f.match_date DESC LIMIT 5
                        ");
                        if($recent->num_rows == 0):
                        ?>
                            <tr><td colspan="4" style="text-align: center;">No matches played yet</td></tr>
                        <?php else:
                        while($row = $recent->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo date('M j', strtotime($row['match_date'])); ?></td>
                            <td><?php echo $row['home_name']; ?></td>
                            <td><strong><?php echo $row['home_score']; ?> - <?php echo $row['away_score']; ?></strong></td>
                            <td><?php echo $row['away_name']; ?></td>
                        </tr>
                        <?php endwhile; endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="top-scorers">
                <h2>Top Scorers</h2>
                <table class="data-table">
                    <thead><tr><th>Player</th><th>Team</th><th>Goals</th></tr></thead>
                    <tbody>
                        <?php
                        $topScorers = $conn->query("
                            SELECT p.name, t.name as team_name, p.goals_scored 
                            FROM players p JOIN teams t ON p.team_id = t.id
                            WHERE p.goals_scored > 0 ORDER BY p.goals_scored DESC LIMIT 5
                        ");
                        if($topScorers->num_rows == 0):
                            echo '<tr><td colspan="3" style="text-align: center;">No goals recorded yet</td></tr>';
                        else:
                            while($scorer = $topScorers->fetch_assoc()):
                                echo '<tr><td>'.$scorer['name'].'</td><td>'.$scorer['team_name'].'</td><td><strong>'.$scorer['goals_scored'].'</strong></td></tr>';
                            endwhile;
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script src="script.js"></script>
</body>
</html>