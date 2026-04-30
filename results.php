<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['save_result'])) {
        $match_id = $_POST['match_id'];
        $home_score = $_POST['home_score'];
        $away_score = $_POST['away_score'];
        
        $stmt = $conn->prepare("UPDATE fixtures SET home_score=?, away_score=?, status='played' WHERE id=?");
        $stmt->bind_param("iii", $home_score, $away_score, $match_id);
        $stmt->execute();
        $message = "Result saved!";
    } elseif (isset($_POST['update_result'])) {
        $match_id = $_POST['match_id'];
        $home_score = $_POST['home_score'];
        $away_score = $_POST['away_score'];
        
        $stmt = $conn->prepare("UPDATE fixtures SET home_score=?, away_score=? WHERE id=?");
        $stmt->bind_param("iii", $home_score, $away_score, $match_id);
        $stmt->execute();
        $message = "Result updated!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Results - Football League</title>
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
                <li><a href="results.php" class="active">📝 Results</a></li>
                <li><a href="standings.php">📊 Standings</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header><h1>Match Results</h1></header>
            
            <?php if(isset($message)): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="results-form">
                <h2>Enter New Result</h2>
                <form method="POST">
                    <input type="hidden" name="save_result" value="1">
                    <div class="form-group">
                        <label>Select Match:</label>
                        <select name="match_id" required>
                            <option value="">-- Select Match --</option>
                            <?php
                            $matches = $conn->query("
                                SELECT f.id, h.name as home, a.name as away, f.match_date
                                FROM fixtures f
                                JOIN teams h ON f.home_team_id = h.id
                                JOIN teams a ON f.away_team_id = a.id
                                WHERE f.status != 'played'
                                ORDER BY f.match_date ASC
                            ");
                            while($match = $matches->fetch_assoc()):
                            ?>
                            <option value="<?php echo $match['id']; ?>">
                                <?php echo $match['home']; ?> vs <?php echo $match['away']; ?> (<?php echo $match['match_date']; ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="score-inputs">
                        <div class="form-group">
                            <label>Home Score:</label>
                            <input type="number" name="home_score" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Away Score:</label>
                            <input type="number" name="away_score" min="0" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Save Result</button>
                </form>
            </div>

            <div class="results-form">
                <h2>Edit Existing Result</h2>
                <form method="POST">
                    <input type="hidden" name="update_result" value="1">
                    <div class="form-group">
                        <label>Select Match:</label>
                        <select name="match_id" required onchange="loadResult(this)">
                            <option value="">-- Select Match --</option>
                            <?php
                            $playedMatches = $conn->query("
                                SELECT f.id, h.name as home, a.name as away, f.home_score, f.away_score
                                FROM fixtures f
                                JOIN teams h ON f.home_team_id = h.id
                                JOIN teams a ON f.away_team_id = a.id
                                WHERE f.status = 'played'
                                ORDER BY f.match_date DESC
                            ");
                            while($match = $playedMatches->fetch_assoc()):
                            ?>
                            <option value="<?php echo $match['id']; ?>" data-home="<?php echo $match['home_score']; ?>" data-away="<?php echo $match['away_score']; ?>">
                                <?php echo $match['home']; ?> vs <?php echo $match['away']; ?> (<?php echo $match['home_score']; ?>-<?php echo $match['away_score']; ?>)
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="score-inputs">
                        <div class="form-group">
                            <label>Home Score:</label>
                            <input type="number" name="home_score" id="edit_home" min="0" required>
                        </div>
                        <div class="form-group">
                            <label>Away Score:</label>
                            <input type="number" name="away_score" id="edit_away" min="0" required>
                        </div>
                    </div>
                    <button type="submit" class="btn-primary">Update Result</button>
                </form>
            </div>

            <div class="previous-results">
                <h2>Previous Results</h2>
                <table class="data-table">
                    <thead><tr><th>Date</th><th>Home</th><th>Score</th><th>Away</th></tr></thead>
                    <tbody>
                        <?php
                        $results = $conn->query("
                            SELECT f.*, h.name as home, a.name as away 
                            FROM fixtures f
                            JOIN teams h ON f.home_team_id = h.id
                            JOIN teams a ON f.away_team_id = a.id
                            WHERE f.status = 'played'
                            ORDER BY f.match_date DESC
                        ");
                        while($result = $results->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($result['match_date'])); ?></td>
                            <td><?php echo $result['home']; ?></td>
                            <td><strong><?php echo $result['home_score']; ?> - <?php echo $result['away_score']; ?></strong></td>
                            <td><?php echo $result['away']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        function loadResult(select) {
            const option = select.options[select.selectedIndex];
            if (option.value) {
                document.getElementById('edit_home').value = option.getAttribute('data-home');
                document.getElementById('edit_away').value = option.getAttribute('data-away');
            }
        }
    </script>
</body>
</html>