<?php
require_once 'db_connect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standings - Football League</title>
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
                <li><a href="standings.php" class="active">📊 Standings</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header>
                <h1>League Standings</h1>
                <button class="btn-primary" onclick="window.print()">🖨️ Print</button>
            </header>

            <!-- Main Standings Table -->
            <div class="standings-container">
                <h2>🏆 League Table</h2>
                <?php
                // Fix: Use a simpler query approach
                $teams = $conn->query("SELECT id, name FROM teams ORDER BY name");
                
                if($teams->num_rows == 0) {
                    echo '<div class="no-data">No teams added yet. Please add teams first.</div>';
                } else {
                    // Calculate standings for each team
                    $standings_data = [];
                    while($team = $teams->fetch_assoc()) {
                        $team_id = $team['id'];
                        
                        // Get all matches for this team
                        $matches = $conn->query("
                            SELECT 
                                home_score, away_score, status,
                                CASE 
                                    WHEN home_team_id = $team_id THEN 'home'
                                    ELSE 'away'
                                END as venue
                            FROM fixtures 
                            WHERE (home_team_id = $team_id OR away_team_id = $team_id) 
                            AND status = 'played'
                        ");
                        
                        $played = 0;
                        $wins = 0;
                        $draws = 0;
                        $losses = 0;
                        $goals_for = 0;
                        $goals_against = 0;
                        $points = 0;
                        
                        while($match = $matches->fetch_assoc()) {
                            $played++;
                            
                            if($match['venue'] == 'home') {
                                $goals_for += $match['home_score'];
                                $goals_against += $match['away_score'];
                                
                                if($match['home_score'] > $match['away_score']) {
                                    $wins++;
                                    $points += 3;
                                } elseif($match['home_score'] == $match['away_score']) {
                                    $draws++;
                                    $points += 1;
                                } else {
                                    $losses++;
                                }
                            } else {
                                $goals_for += $match['away_score'];
                                $goals_against += $match['home_score'];
                                
                                if($match['away_score'] > $match['home_score']) {
                                    $wins++;
                                    $points += 3;
                                } elseif($match['away_score'] == $match['home_score']) {
                                    $draws++;
                                    $points += 1;
                                } else {
                                    $losses++;
                                }
                            }
                        }
                        
                        $goal_difference = $goals_for - $goals_against;
                        
                        $standings_data[] = [
                            'id' => $team_id,
                            'name' => $team['name'],
                            'played' => $played,
                            'wins' => $wins,
                            'draws' => $draws,
                            'losses' => $losses,
                            'goals_for' => $goals_for,
                            'goals_against' => $goals_against,
                            'gd' => $goal_difference,
                            'points' => $points
                        ];
                    }
                    
                    // Sort standings by points, then goal difference, then goals for
                    usort($standings_data, function($a, $b) {
                        if($a['points'] != $b['points']) {
                            return $b['points'] - $a['points'];
                        }
                        if($a['gd'] != $b['gd']) {
                            return $b['gd'] - $a['gd'];
                        }
                        return $b['goals_for'] - $a['goals_for'];
                    });
                    ?>
                    
                    <div class="table-responsive">
                        <table class="standings-table">
                            <thead>
                                <tr>
                                    <th>Pos</th>
                                    <th>Team</th>
                                    <th>P</th>
                                    <th>W</th>
                                    <th>D</th>
                                    <th>L</th>
                                    <th>GF</th>
                                    <th>GA</th>
                                    <th>GD</th>
                                    <th>Pts</th>
                                    <th>Form</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $position = 1;
                                foreach($standings_data as $team):
                                    if($team['played'] == 0) continue; // Skip teams with no matches
                                    
                                    // Get last 5 match results for form guide
                                    $form_results = [];
                                    $form_query = $conn->query("
                                        SELECT 
                                            home_team_id, away_team_id, home_score, away_score,
                                            CASE 
                                                WHEN home_team_id = {$team['id']} THEN 'home'
                                                ELSE 'away'
                                            END as venue
                                        FROM fixtures 
                                        WHERE (home_team_id = {$team['id']} OR away_team_id = {$team['id']}) 
                                        AND status = 'played'
                                        ORDER BY match_date DESC, match_time DESC
                                        LIMIT 5
                                    ");
                                    
                                    while($f = $form_query->fetch_assoc()) {
                                        if($f['venue'] == 'home') {
                                            if($f['home_score'] > $f['away_score']) $form_results[] = 'W';
                                            elseif($f['home_score'] == $f['away_score']) $form_results[] = 'D';
                                            else $form_results[] = 'L';
                                        } else {
                                            if($f['away_score'] > $f['home_score']) $form_results[] = 'W';
                                            elseif($f['away_score'] == $f['home_score']) $form_results[] = 'D';
                                            else $form_results[] = 'L';
                                        }
                                    }
                                ?>
                                <tr class="<?php echo $position <= 4 ? 'top-four' : ''; ?>">
                                    <td class="position"><?php echo $position++; ?></td>
                                    <td class="team-name">
                                        <strong><?php echo htmlspecialchars($team['name']); ?></strong>
                                    </td>
                                    <td><?php echo $team['played']; ?></td>
                                    <td><?php echo $team['wins']; ?></td>
                                    <td><?php echo $team['draws']; ?></td>
                                    <td><?php echo $team['losses']; ?></td>
                                    <td><?php echo $team['goals_for']; ?></td>
                                    <td><?php echo $team['goals_against']; ?></td>
                                    <td class="<?php echo $team['gd'] >= 0 ? 'positive' : 'negative'; ?>">
                                        <?php echo $team['gd'] >= 0 ? '+' . $team['gd'] : $team['gd']; ?>
                                    </td>
                                    <td class="points"><?php echo $team['points']; ?></td>
                                    <td class="form-guide">
                                        <?php 
                                        if(empty($form_results)) {
                                            echo '-';
                                        } else {
                                            foreach($form_results as $result) {
                                                $class = $result == 'W' ? 'form-w' : ($result == 'D' ? 'form-d' : 'form-l');
                                                echo "<span class='form-badge $class'>$result</span>";
                                            }
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if(empty($standings_data) || $position == 1): ?>
                                <tr>
                                    <td colspan="11" style="text-align: center; padding: 40px;">
                                        No matches have been played yet.<br>
                                        <small>Please enter match results to see the standings.</small>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                <?php } ?>
            </div>

            <!-- Match Log -->
            <div class="match-log">
                <h2>📋 Match Log</h2>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Home Team</th>
                                <th></th>
                                <th></th>
                                <th>Away Team</th>
                                <th>Result</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $log = $conn->query("
                                SELECT 
                                    f.*, 
                                    h.name as home_name, 
                                    a.name as away_name 
                                FROM fixtures f 
                                JOIN teams h ON f.home_team_id = h.id 
                                JOIN teams a ON f.away_team_id = a.id
                                WHERE f.status = 'played' 
                                ORDER BY f.match_date DESC, f.match_time DESC
                                LIMIT 20
                            ");
                            
                            if($log->num_rows == 0) {
                                echo '<tr><td colspan="6" style="text-align: center;">No matches played yet</td></tr>';
                            } else {
                                while($match = $log->fetch_assoc()):
                                    $is_home_win = $match['home_score'] > $match['away_score'];
                                    $is_away_win = $match['away_score'] > $match['home_score'];
                                    $is_draw = $match['home_score'] == $match['away_score'];
                            ?>
                            <tr class="<?php echo $is_home_win ? 'home-win' : ($is_away_win ? 'away-win' : 'draw'); ?>">
                                <td><?php echo date('M j, Y', strtotime($match['match_date'])); ?></td>
                                <td class="team-home"><strong><?php echo htmlspecialchars($match['home_name']); ?></strong></td>
                                <td class="score-cell"><?php echo $match['home_score']; ?></td>
                                <td class="score-cell">-</td>
                                <td class="score-cell"><?php echo $match['away_score']; ?></td>
                                <td class="team-away"><strong><?php echo htmlspecialchars($match['away_name']); ?></strong></td>
                                <td>
                                    <?php 
                                    if($is_home_win) echo '🏆 Home Win';
                                    elseif($is_away_win) echo '🏆 Away Win';
                                    else echo '🤝 Draw';
                                    ?>
                                </td>
                            </tr>
                            <?php 
                                endwhile;
                            } 
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- League Statistics -->
            <div class="stats-summary">
                <h2>📊 League Statistics</h2>
                <div class="stats-grid-small">
                    <?php
                    // Get total goals
                    $goals = $conn->query("SELECT SUM(home_score) as total_home, SUM(away_score) as total_away FROM fixtures WHERE status='played'")->fetch_assoc();
                    $totalGoals = ($goals['total_home'] ?? 0) + ($goals['total_away'] ?? 0);
                    
                    // Get total matches
                    $totalMatches = $conn->query("SELECT COUNT(*) as count FROM fixtures WHERE status='played'")->fetch_assoc();
                    $matchesPlayed = $totalMatches['count'];
                    
                    // Average goals per match
                    $avgGoals = $matchesPlayed > 0 ? round($totalGoals / $matchesPlayed, 2) : 0;
                    
                    // Highest scoring match
                    $highestScoring = $conn->query("
                        SELECT 
                            h.name as home_name, 
                            a.name as away_name, 
                            home_score, 
                            away_score,
                            (home_score + away_score) as total
                        FROM fixtures f
                        JOIN teams h ON f.home_team_id = h.id
                        JOIN teams a ON f.away_team_id = a.id
                        WHERE f.status = 'played'
                        ORDER BY total DESC
                        LIMIT 1
                    ")->fetch_assoc();
                    
                    // Most wins
                    $mostWins = $conn->query("
                        SELECT t.name, COUNT(*) as wins
                        FROM fixtures f
                        JOIN teams t ON (f.home_team_id = t.id AND f.home_score > f.away_score)
                                   OR (f.away_team_id = t.id AND f.away_score > f.home_score)
                        WHERE f.status = 'played'
                        GROUP BY t.id
                        ORDER BY wins DESC
                        LIMIT 1
                    ")->fetch_assoc();
                    ?>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $totalGoals; ?></div>
                        <div class="stat-label">Total Goals</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $matchesPlayed; ?></div>
                        <div class="stat-label">Matches Played</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $avgGoals; ?></div>
                        <div class="stat-label">Avg Goals/Match</div>
                    </div>
                    <?php if($highestScoring): ?>
                    <div class="stat-item">
                        <div class="stat-value"><?php echo $highestScoring['total']; ?></div>
                        <div class="stat-label">Highest Scoring</div>
                        <small><?php echo htmlspecialchars($highestScoring['home_name']); ?> <?php echo $highestScoring['home_score']; ?>-<?php echo $highestScoring['away_score']; ?> <?php echo htmlspecialchars($highestScoring['away_name']); ?></small>
                    </div>
                    <?php endif; ?>
                    <?php if($mostWins): ?>
                    <div class="stat-item">
                        <div class="stat-value">🏆</div>
                        <div class="stat-label">Most Wins</div>
                        <small><?php echo htmlspecialchars($mostWins['name']); ?> (<?php echo $mostWins['wins']; ?> wins)</small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</body>
</html>