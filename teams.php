<?php
require_once 'db_connect.php';

// Handle form submission for Add/Edit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $name = $_POST['name'];
        $city = $_POST['city'];
        $coach = $_POST['coach'];
        $founded_year = $_POST['founded_year'];
        
        if ($_POST['action'] == 'add') {
            $stmt = $conn->prepare("INSERT INTO teams (name, city, coach, founded_year) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $name, $city, $coach, $founded_year);
            if ($stmt->execute()) {
                $success = "Team added successfully!";
            } else {
                $error = "Error adding team: " . $conn->error;
            }
        } elseif ($_POST['action'] == 'edit') {
            $id = $_POST['id'];
            $stmt = $conn->prepare("UPDATE teams SET name=?, city=?, coach=?, founded_year=? WHERE id=?");
            $stmt->bind_param("sssii", $name, $city, $coach, $founded_year, $id);
            if ($stmt->execute()) {
                $success = "Team updated successfully!";
            } else {
                $error = "Error updating team: " . $conn->error;
            }
        }
    } elseif (isset($_POST['delete_id'])) {
        $id = $_POST['delete_id'];
        
        // First check if team has any fixtures
        $check_fixtures = $conn->prepare("SELECT COUNT(*) as count FROM fixtures WHERE home_team_id = ? OR away_team_id = ?");
        $check_fixtures->bind_param("ii", $id, $id);
        $check_fixtures->execute();
        $fixtures_count = $check_fixtures->get_result()->fetch_assoc()['count'];
        
        // Check if team has any players
        $check_players = $conn->prepare("SELECT COUNT(*) as count FROM players WHERE team_id = ?");
        $check_players->bind_param("i", $id);
        $check_players->execute();
        $players_count = $check_players->get_result()->fetch_assoc()['count'];
        
        if ($fixtures_count > 0 || $players_count > 0) {
            // Cannot delete - has related records
            $error = "Cannot delete this team because it has ";
            if ($fixtures_count > 0) $error .= "$fixtures_count fixture(s) ";
            if ($players_count > 0) $error .= "$players_count player(s) ";
            $error .= "linked to it. Please delete the related data first.";
        } else {
            // Safe to delete
            $stmt = $conn->prepare("DELETE FROM teams WHERE id=?");
            $stmt->bind_param("i", $id);
            if ($stmt->execute()) {
                $success = "Team deleted successfully!";
            } else {
                $error = "Error deleting team: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams - Football League</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <nav class="sidebar">
            <div class="sidebar-header"><h2>⚽ Football League</h2></div>
            <ul class="sidebar-menu">
                <li><a href="index.php">📊 Dashboard</a></li>
                <li><a href="teams.php" class="active">🏆 Teams</a></li>
                <li><a href="players.php">👥 Players</a></li>
                <li><a href="fixtures.php">📅 Fixtures</a></li>
                <li><a href="results.php">📝 Results</a></li>
                <li><a href="standings.php">📊 Standings</a></li>
            </ul>
        </nav>

        <main class="main-content">
            <header>
                <h1>Manage Teams</h1>
                <button class="btn-primary" onclick="openTeamModal('add')">+ Add Team</button>
            </header>

            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if(isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="teams-grid">
                <?php
                $teams = $conn->query("SELECT * FROM teams ORDER BY name");
                if($teams->num_rows == 0): ?>
                    <div class="no-data">No teams yet. Click "Add Team" to get started.</div>
                <?php else:
                while($team = $teams->fetch_assoc()):
                    // Get counts for this team
                    $fixture_count = $conn->query("SELECT COUNT(*) as count FROM fixtures WHERE home_team_id = {$team['id']} OR away_team_id = {$team['id']}")->fetch_assoc();
                    $player_count = $conn->query("SELECT COUNT(*) as count FROM players WHERE team_id = {$team['id']}")->fetch_assoc();
                ?>
                <div class="team-card">
                    <div class="team-logo">🏆</div>
                    <h3><?php echo htmlspecialchars($team['name']); ?></h3>
                    <p>📍 <?php echo htmlspecialchars($team['city'] ?: 'Not specified'); ?></p>
                    <p>👨‍🏫 <?php echo htmlspecialchars($team['coach'] ?: 'No coach'); ?></p>
                    <p>📅 <?php echo $team['founded_year'] ?: 'N/A'; ?></p>
                    <div class="team-stats">
                        <small>⚽ <?php echo $fixture_count['count']; ?> fixtures | 👥 <?php echo $player_count['count']; ?> players</small>
                    </div>
                    <div class="team-actions">
                        <button class="btn-edit" onclick="editTeam(<?php echo $team['id']; ?>)">Edit</button>
                        <button class="btn-delete" onclick="confirmDelete(<?php echo $team['id']; ?>, '<?php echo addslashes($team['name']); ?>', <?php echo $fixture_count['count']; ?>, <?php echo $player_count['count']; ?>)">Delete</button>
                    </div>
                </div>
                <?php endwhile; endif; ?>
            </div>
        </main>
    </div>

    <!-- Team Form Modal -->
    <div id="teamModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeTeamModal()">&times;</span>
            <h2 id="modalTitle">Add Team</h2>
            <form method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="teamId">
                <div class="form-group">
                    <label>Team Name *</label>
                    <input type="text" name="name" id="teamName" required>
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" id="teamCity">
                </div>
                <div class="form-group">
                    <label>Coach</label>
                    <input type="text" name="coach" id="teamCoach">
                </div>
                <div class="form-group">
                    <label>Founded Year</label>
                    <input type="number" name="founded_year" id="teamFounded">
                </div>
                <button type="submit" class="btn-primary">Save Team</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeDeleteModal()">&times;</span>
            <h2>Confirm Delete</h2>
            <p>Are you sure you want to delete <strong id="deleteTeamName"></strong>?</p>
            <div id="deleteWarning" style="display: none; background: #fff3cd; padding: 10px; margin: 10px 0; border-radius: 5px; color: #856404;">
                ⚠️ <strong>Warning:</strong> This team has related data that must be removed first.
            </div>
            <form method="POST" id="deleteForm">
                <input type="hidden" name="delete_id" id="deleteId">
                <div style="display: flex; gap: 10px; margin-top: 20px;">
                    <button type="submit" class="btn-delete" id="confirmDeleteBtn">Yes, Delete</button>
                    <button type="button" class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                </div>
            </form>
            <div id="deleteInstructions" style="display: none; margin-top: 15px; padding: 10px; background: #d1ecf1; border-radius: 5px; font-size: 14px;">
                <strong>How to delete this team:</strong><br>
                1. First delete all players from this team<br>
                2. Delete all fixtures involving this team<br>
                3. Then delete the team
            </div>
        </div>
    </div>

    <script>
        function openTeamModal(action, id = null) {
            const modal = document.getElementById('teamModal');
            if (action === 'add') {
                document.getElementById('modalTitle').innerHTML = 'Add Team';
                document.getElementById('formAction').value = 'add';
                document.getElementById('teamId').value = '';
                document.getElementById('teamName').value = '';
                document.getElementById('teamCity').value = '';
                document.getElementById('teamCoach').value = '';
                document.getElementById('teamFounded').value = '';
                modal.style.display = 'block';
            }
        }
        
        function editTeam(id) {
            fetch(`api/get_team.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modalTitle').innerHTML = 'Edit Team';
                    document.getElementById('formAction').value = 'edit';
                    document.getElementById('teamId').value = data.id;
                    document.getElementById('teamName').value = data.name;
                    document.getElementById('teamCity').value = data.city;
                    document.getElementById('teamCoach').value = data.coach;
                    document.getElementById('teamFounded').value = data.founded_year;
                    document.getElementById('teamModal').style.display = 'block';
                })
                .catch(error => console.error('Error:', error));
        }
        
        function confirmDelete(id, name, fixtureCount, playerCount) {
            document.getElementById('deleteId').value = id;
            document.getElementById('deleteTeamName').innerHTML = name;
            
            const deleteBtn = document.getElementById('confirmDeleteBtn');
            const warningDiv = document.getElementById('deleteWarning');
            const instructionsDiv = document.getElementById('deleteInstructions');
            
            if (fixtureCount > 0 || playerCount > 0) {
                // Can't delete - show warning
                warningDiv.style.display = 'block';
                instructionsDiv.style.display = 'block';
                let warningText = "⚠️ Cannot delete this team because it has ";
                if (playerCount > 0) warningText += `${playerCount} player(s) `;
                if (fixtureCount > 0) warningText += `${fixtureCount} fixture(s) `;
                warningText += "linked to it.";
                warningDiv.innerHTML = warningText;
                deleteBtn.disabled = true;
                deleteBtn.style.opacity = "0.5";
                deleteBtn.style.cursor = "not-allowed";
            } else {
                // Can delete
                warningDiv.style.display = 'none';
                instructionsDiv.style.display = 'none';
                deleteBtn.disabled = false;
                deleteBtn.style.opacity = "1";
                deleteBtn.style.cursor = "pointer";
            }
            
            document.getElementById('deleteModal').style.display = 'block';
        }
        
        function closeTeamModal() { 
            document.getElementById('teamModal').style.display = 'none'; 
        }
        
        function closeDeleteModal() { 
            document.getElementById('deleteModal').style.display = 'none'; 
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>