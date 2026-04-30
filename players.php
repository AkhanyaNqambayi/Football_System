<?php
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $team_id = $_POST['team_id'];
        $name = $_POST['name'];
        $position = $_POST['position'];
        $jersey_number = $_POST['jersey_number'];
        $age = $_POST['age'];
        $nationality = $_POST['nationality'];
        
        if ($_POST['action'] == 'add') {
            $stmt = $conn->prepare("INSERT INTO players (team_id, name, position, jersey_number, age, nationality) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issiis", $team_id, $name, $position, $jersey_number, $age, $nationality);
            $stmt->execute();
        } elseif ($_POST['action'] == 'edit') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("UPDATE players SET team_id=?, name=?, position=?, jersey_number=?, age=?, nationality=? WHERE id=?");
            $stmt->bind_param("issiisi", $team_id, $name, $position, $jersey_number, $age, $nationality, $id);
            $stmt->execute();
        }
        header("Location: players.php");
        exit();
    } elseif (isset($_POST['delete_id'])) {
        $stmt = $conn->prepare("DELETE FROM players WHERE id=?");
        $stmt->bind_param("i", $_POST['delete_id']);
        $stmt->execute();
        header("Location: players.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Players - Football League</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <div class="sidebar-header"><h2>⚽ Football League</h2></div>
            <ul class="sidebar-menu">
                <li><a href="index.php">📊 Dashboard</a></li>
                <li><a href="teams.php">🏆 Teams</a></li>
                <li><a href="players.php" class="active">👥 Players</a></li>
                <li><a href="fixtures.php">📅 Fixtures</a></li>
                <li><a href="results.php">📝 Results</a></li>
                <li><a href="standings.php">📊 Standings</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header>
                <h1>Manage Players</h1>
                <button class="btn-primary" onclick="openPlayerModal('add')">+ Add Player</button>
            </header>

            <div class="filter-section">
                <label>Filter by Team:</label>
                <select id="teamFilter" onchange="filterPlayers()">
                    <option value="">All Teams</option>
                    <?php
                    $teams = $conn->query("SELECT id, name FROM teams ORDER BY name");
                    while($team = $teams->fetch_assoc()):
                    ?>
                    <option value="<?php echo $team['id']; ?>"><?php echo $team['name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div id="playersList"></div>
        </main>
    </div>

    <div id="playerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closePlayerModal()">&times;</span>
            <h2 id="playerModalTitle">Add Player</h2>
            <form method="POST" id="playerForm">
                <input type="hidden" name="action" id="playerFormAction">
                <input type="hidden" name="id" id="playerId">
                <div class="form-group">
                    <label>Team *</label>
                    <select name="team_id" id="playerTeam" required>
                        <option value="">Select Team</option>
                        <?php
                        $teams = $conn->query("SELECT id, name FROM teams ORDER BY name");
                        while($team = $teams->fetch_assoc()):
                        ?>
                        <option value="<?php echo $team['id']; ?>"><?php echo $team['name']; ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Player Name *</label>
                    <input type="text" name="name" id="playerName" required>
                </div>
                <div class="form-group">
                    <label>Position *</label>
                    <select name="position" id="playerPosition" required>
                        <option value="Goalkeeper">Goalkeeper</option>
                        <option value="Defender">Defender</option>
                        <option value="Midfielder">Midfielder</option>
                        <option value="Forward">Forward</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Jersey Number</label>
                    <input type="number" name="jersey_number" id="playerJersey">
                </div>
                <div class="form-group">
                    <label>Age</label>
                    <input type="number" name="age" id="playerAge">
                </div>
                <div class="form-group">
                    <label>Nationality</label>
                    <input type="text" name="nationality" id="playerNationality">
                </div>
                <button type="submit" class="btn-primary">Save Player</button>
            </form>
        </div>
    </div>

    <div id="deletePlayerModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDeletePlayerModal()">&times;</span>
            <h2>Confirm Delete</h2>
            <p>Delete <strong id="deletePlayerName"></strong>?</p>
            <form method="POST">
                <input type="hidden" name="delete_id" id="deletePlayerId">
                <button type="submit" class="btn-delete">Delete</button>
                <button type="button" class="btn-secondary" onclick="closeDeletePlayerModal()">Cancel</button>
            </form>
        </div>
    </div>

    <script>
        function filterPlayers() {
            const teamId = document.getElementById('teamFilter').value;
            fetch(`api/get_players.php?team_id=${teamId}`)
                .then(response => response.json())
                .then(data => {
                    const container = document.getElementById('playersList');
                    if (data.length === 0) {
                        container.innerHTML = '<div class="no-data">No players found. Add players to teams.</div>';
                        return;
                    }
                    let html = '<table class="data-table"><thead><tr><th>Name</th><th>Team</th><th>Position</th><th>Jersey</th><th>Age</th><th>Nationality</th><th>Actions</th></tr></thead><tbody>';
                    data.forEach(player => {
                        html += `<tr>
                            <td>${player.name}</td>
                            <td>${player.team_name}</td>
                            <td>${player.position}</td>
                            <td>${player.jersey_number || '-'}</td>
                            <td>${player.age || '-'}</td>
                            <td>${player.nationality || '-'}</td>
                            <td>
                                <button class="btn-edit" onclick="editPlayer(${player.id})">Edit</button>
                                <button class="btn-delete" onclick="confirmDeletePlayer(${player.id}, '${player.name}')">Delete</button>
                            </td>
                        </tr>`;
                    });
                    html += '</tbody></table>';
                    container.innerHTML = html;
                });
        }
        
        function openPlayerModal(action, id = null) {
            const modal = document.getElementById('playerModal');
            if (action === 'add') {
                document.getElementById('playerModalTitle').innerHTML = 'Add Player';
                document.getElementById('playerFormAction').value = 'add';
                document.getElementById('playerId').value = '';
                document.getElementById('playerForm').reset();
            }
            modal.style.display = 'block';
        }
        
        function editPlayer(id) {
            fetch(`api/get_player.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('playerModalTitle').innerHTML = 'Edit Player';
                    document.getElementById('playerFormAction').value = 'edit';
                    document.getElementById('playerId').value = data.id;
                    document.getElementById('playerTeam').value = data.team_id;
                    document.getElementById('playerName').value = data.name;
                    document.getElementById('playerPosition').value = data.position;
                    document.getElementById('playerJersey').value = data.jersey_number;
                    document.getElementById('playerAge').value = data.age;
                    document.getElementById('playerNationality').value = data.nationality;
                    document.getElementById('playerModal').style.display = 'block';
                });
        }
        
        function confirmDeletePlayer(id, name) {
            document.getElementById('deletePlayerId').value = id;
            document.getElementById('deletePlayerName').innerHTML = name;
            document.getElementById('deletePlayerModal').style.display = 'block';
        }
        
        function closePlayerModal() { document.getElementById('playerModal').style.display = 'none'; }
        function closeDeletePlayerModal() { document.getElementById('deletePlayerModal').style.display = 'none'; }
        
        filterPlayers();
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) event.target.style.display = 'none';
        }
    </script>
</body>
</html>