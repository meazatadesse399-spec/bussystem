<?php
session_start();
include '../php/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') header("Location: ../php/auth.php");

if (isset($_POST['add'])) {
    $stmt = $conn->prepare("INSERT INTO bus_stations (station_name, city, address, contact_phone) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $_POST['name'], $_POST['city'], $_POST['addr'], $_POST['phone']);
    $stmt->execute();
} elseif (isset($_POST['edit_station'])) {
    $id = $_POST['station_id'];
    $name = $_POST['name'];
    $city = $_POST['city'];
    $addr = $_POST['addr'];
    $phone = $_POST['phone'];
    
    $stmt = $conn->prepare("UPDATE bus_stations SET station_name = ?, city = ?, address = ?, contact_phone = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $name, $city, $addr, $phone, $id);
    if ($stmt->execute()) $message = "Station updated successfully!";
    else $message = "Error updating station.";
} elseif (isset($_POST['delete'])) {
    $conn->query("DELETE FROM bus_stations WHERE id=" . $_POST['delete']);
    $message = "Station deleted successfully.";
}

if (isset($_POST['add'])) {
    $message = "Station added successfully.";
}

$stations = $conn->query("SELECT * FROM bus_stations");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Stations</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="admin-brand">
                <h2><i class="fas fa-bus"></i> Fiker Admin</h2>
                <p style="color: #bdc3c7; font-size: 0.9rem;">Welcome, <?php echo $_SESSION['username']; ?></p>
            </div>
            <div class="admin-menu">
                <a href="admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="bookings.php"><i class="fas fa-ticket-alt"></i> Manage Bookings</a>
                <a href="buses.php"><i class="fas fa-bus"></i> Manage Buses</a>
                <a href="schedules.php"><i class="fas fa-calendar-alt"></i> Manage Schedules</a>
                <a href="stations.php" class="active"><i class="fas fa-map-marker-alt"></i> Manage Stations</a>
                <a href="../index.php#routes" target="_blank"><i class="fas fa-route"></i> Live Routes</a>
                <a href="users.php"><i class="fas fa-users"></i> Manage Users</a>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
                <a href="../php/auth.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        <div class="admin-content">
            <div class="dashboard-header">
                <h1>Manage Stations</h1>
                <button onclick="document.getElementById('addBox').style.display='block'" class="btn-primary">Add Station</button>
            </div>

            <?php if (isset($message) && $message): ?>
                <div class="alert alert-success" style="padding: 15px; background: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <div class="bookings-table">
                <table>
                    <thead><tr><th>Name</th><th>City</th><th>Address</th><th>Phone</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($r=$stations->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $r['station_name']; ?></td>
                            <td><?php echo $r['city']; ?></td>
                            <td><?php echo $r['address']; ?></td>
                            <td><?php echo $r['contact_phone']; ?></td>
                            <td class="action-buttons">
                                <button type="button" class="btn-confirm" 
                                        onclick='editStation(<?php echo json_encode($r); ?>)' title="Edit Station">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this station?')">
                                    <input type="hidden" name="delete" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="btn-cancel" title="Delete Station">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Edit Station Modal -->
    <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index: 2000;">
        <div style="background:white; width:400px; margin: 100px auto; padding:20px; border-radius:10px; box-shadow: 0 5px 25px rgba(0,0,0,0.2);">
            <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="margin:0;">Edit Station</h2>
                <button type="button" onclick="closeModal('editModal')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="station_id" id="edit_station_id">
                <div class="form-group">
                    <label>Station Name</label>
                    <input type="text" name="name" id="edit_station_name" required>
                </div>
                <div class="form-group">
                    <label>City</label>
                    <input type="text" name="city" id="edit_station_city" required>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="addr" id="edit_station_addr">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" name="phone" id="edit_station_phone">
                </div>
                <div style="margin-top:20px; display:flex; gap:10px;">
                    <button type="submit" name="edit_station" class="btn-primary" style="flex:1;">Update Station</button>
                    <button type="button" onclick="closeModal('editModal')" class="btn-cancel" style="flex:1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Station Modal -->
    <div id="addBox" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index: 2000;">
        <div style="background:white; width:400px; margin: 100px auto; padding:20px; border-radius:10px; box-shadow: 0 5px 25px rgba(0,0,0,0.2);">
            <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="margin:0;">Add New Station</h2>
                <button type="button" onclick="closeModal('addBox')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group"><label>Name</label><input name="name" required></div>
                <div class="form-group"><label>City</label><input name="city" required></div>
                <div class="form-group"><label>Address</label><input name="addr"></div>
                <div class="form-group"><label>Phone</label><input name="phone"></div>
                <div style="margin-top:20px; display:flex; gap:10px;">
                    <button type="submit" name="add" class="btn-primary" style="flex:1;">Save Station</button>
                    <button type="button" onclick="closeModal('addBox')" class="btn-cancel" style="flex:1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editStation(station) {
            document.getElementById('edit_station_id').value = station.id;
            document.getElementById('edit_station_name').value = station.station_name;
            document.getElementById('edit_station_city').value = station.city;
            document.getElementById('edit_station_addr').value = station.address;
            document.getElementById('edit_station_phone').value = station.contact_phone;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
    </script>
</body>
</html>
