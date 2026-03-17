<?php
session_start();
include '../php/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../php/auth.php?action=login");
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_schedule'])) {
        $bus = $_POST['bus_id'];
        $dep = $_POST['dep_id'];
        $arr = $_POST['arr_id'];
        $dep_time = $_POST['dep_time'];
        $arr_time = $_POST['arr_time'];
        $price = $_POST['price'];
        
        // Get total seats
        $res = $conn->query("SELECT total_seats FROM buses WHERE id = $bus");
        $seats = $res->fetch_assoc()['total_seats'];
        
        $stmt = $conn->prepare("INSERT INTO bus_schedules (bus_id, departure_station_id, arrival_station_id, departure_time, arrival_time, price, available_seats) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiissdi", $bus, $dep, $arr, $dep_time, $arr_time, $price, $seats);
        
        if ($stmt->execute()) $message = "Schedule added!";
        else $message = "Error adding schedule.";
    } elseif (isset($_POST['edit_schedule'])) {
        $id = $_POST['schedule_id'];
        $bus = $_POST['bus_id'];
        $dep = $_POST['dep_id'];
        $arr = $_POST['arr_id'];
        $dep_time = $_POST['dep_time'];
        $arr_time = $_POST['arr_time'];
        $price = $_POST['price'];

        $stmt = $conn->prepare("UPDATE bus_schedules SET bus_id = ?, departure_station_id = ?, arrival_station_id = ?, departure_time = ?, arrival_time = ?, price = ? WHERE id = ?");
        $stmt->bind_param("iiissdi", $bus, $dep, $arr, $dep_time, $arr_time, $price, $id);
        if ($stmt->execute()) $message = "Schedule updated successfully!";
        else $message = "Error updating schedule.";
    } elseif (isset($_POST['delete_id'])) {
        $conn->query("DELETE FROM bus_schedules WHERE id = " . $_POST['delete_id']);
        $message = "Schedule deleted successfully.";
    }
}

$schedules = $conn->query("SELECT s.*, b.bus_name, d.station_name as dep, a.station_name as arr FROM bus_schedules s JOIN buses b ON s.bus_id = b.id JOIN bus_stations d ON s.departure_station_id = d.id JOIN bus_stations a ON s.arrival_station_id = a.id ORDER BY departure_time DESC");
$buses = $conn->query("SELECT * FROM buses");
$stations = $conn->query("SELECT * FROM bus_stations");
$stations_arr = [];
while($r = $stations->fetch_assoc()) $stations_arr[] = $r;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Schedules - Fiker Bus</title>
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
                <a href="schedules.php" class="active"><i class="fas fa-calendar-alt"></i> Manage Schedules</a>
                <a href="stations.php"><i class="fas fa-map-marker-alt"></i> Manage Stations</a>
                <a href="../index.php#routes" target="_blank"><i class="fas fa-route"></i> Live Routes</a>
                <a href="users.php"><i class="fas fa-users"></i> Manage Users</a>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
                <a href="../php/auth.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>

        <div class="admin-content">
            <div class="dashboard-header">
                <h1>Manage Schedules</h1>
                <button onclick="document.getElementById('addModal').style.display='block'" class="btn-primary">Add Schedule</button>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success" style="padding: 15px; background: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <div class="bookings-table">
                <table>
                    <thead>
                        <tr>
                            <th>Bus</th>
                            <th>Route</th>
                            <th>Departure</th>
                            <th>Price</th>
                            <th>Seats</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $schedules->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['bus_name']; ?></td>
                            <td><?php echo $row['dep'] . ' -> ' . $row['arr']; ?></td>
                            <td><?php echo date('M d H:i', strtotime($row['departure_time'])); ?></td>
                            <td>$<?php echo $row['price']; ?></td>
                            <td><?php echo $row['available_seats']; ?></td>
                            <td class="action-buttons">
                                <button type="button" class="btn-confirm" 
                                        onclick='editSchedule(<?php echo json_encode($row); ?>)' title="Edit Schedule">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this schedule?')">
                                    <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" class="btn-cancel" title="Delete Schedule">
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

    <!-- Edit Modal -->
    <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index: 2000;">
        <div style="background:white; width:500px; margin: 50px auto; padding:20px; border-radius:10px; box-shadow: 0 5px 25px rgba(0,0,0,0.2);">
            <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="margin:0;">Edit Schedule</h2>
                <button type="button" onclick="closeModal('editModal')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="schedule_id" id="edit_id">
                <div class="form-group">
                    <label>Bus</label>
                    <select name="bus_id" id="edit_bus_id" required>
                        <?php $buses->data_seek(0); while($b = $buses->fetch_assoc()) echo "<option value='{$b['id']}'>{$b['bus_name']}</option>"; ?>
                    </select>
                </div>
                <div style="display:flex; gap:15px;">
                    <div class="form-group" style="flex:1;">
                        <label>From</label>
                        <select name="dep_id" id="edit_dep_id" required>
                            <?php foreach($stations_arr as $s) echo "<option value='{$s['id']}'>{$s['station_name']}</option>"; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>To</label>
                        <select name="arr_id" id="edit_arr_id" required>
                            <?php foreach($stations_arr as $s) echo "<option value='{$s['id']}'>{$s['station_name']}</option>"; ?>
                        </select>
                    </div>
                </div>
                <div style="display:flex; gap:15px;">
                    <div class="form-group" style="flex:1;">
                        <label>Dep Time</label>
                        <input type="datetime-local" name="dep_time" id="edit_dep_time" required>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>Arr Time</label>
                        <input type="datetime-local" name="arr_time" id="edit_arr_time" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Price ($)</label>
                    <input type="number" step="0.01" name="price" id="edit_price" required>
                </div>
                <div style="margin-top:20px; display:flex; gap:10px;">
                    <button type="submit" name="edit_schedule" class="btn-primary" style="flex:1;">Update Schedule</button>
                    <button type="button" onclick="closeModal('editModal')" class="btn-cancel" style="flex:1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Modal -->
    <div id="addModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index: 2000;">
        <div style="background:white; width:500px; margin: 50px auto; padding:20px; border-radius:10px; box-shadow: 0 5px 25px rgba(0,0,0,0.2);">
            <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="margin:0;">Add New Schedule</h2>
                <button type="button" onclick="closeModal('addModal')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Bus</label>
                    <select name="bus_id" required>
                        <?php $buses->data_seek(0); while($b = $buses->fetch_assoc()) echo "<option value='{$b['id']}'>{$b['bus_name']}</option>"; ?>
                    </select>
                </div>
                <div style="display:flex; gap:15px;">
                    <div class="form-group" style="flex:1;">
                        <label>From</label>
                        <select name="dep_id" required>
                            <?php foreach($stations_arr as $s) echo "<option value='{$s['id']}'>{$s['station_name']}</option>"; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>To</label>
                        <select name="arr_id" required>
                            <?php foreach($stations_arr as $s) echo "<option value='{$s['id']}'>{$s['station_name']}</option>"; ?>
                        </select>
                    </div>
                </div>
                <div style="display:flex; gap:15px;">
                    <div class="form-group" style="flex:1;"><label>Dep Time</label><input type="datetime-local" name="dep_time" required></div>
                    <div class="form-group" style="flex:1;"><label>Arr Time</label><input type="datetime-local" name="arr_time" required></div>
                </div>
                <div class="form-group"><label>Price ($)</label><input type="number" step="0.01" name="price" required></div>
                <div style="margin-top:20px; display:flex; gap:10px;">
                    <button name="add_schedule" class="btn-primary" style="flex:1;">Save Schedule</button>
                    <button type="button" onclick="closeModal('addModal')" class="btn-cancel" style="flex:1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editSchedule(s) {
            document.getElementById('edit_id').value = s.id;
            document.getElementById('edit_bus_id').value = s.bus_id;
            document.getElementById('edit_dep_id').value = s.departure_station_id;
            document.getElementById('edit_arr_id').value = s.arrival_station_id;
            document.getElementById('edit_price').value = s.price;
            
            // Format dates for datetime-local input (YYYY-MM-DDTHH:MM)
            const depTime = new Date(s.departure_time);
            const arrTime = new Date(s.arrival_time);
            
            document.getElementById('edit_dep_time').value = formatDateTime(depTime);
            document.getElementById('edit_arr_time').value = formatDateTime(arrTime);
            
            document.getElementById('editModal').style.display = 'block';
        }

        function formatDateTime(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const hours = String(date.getHours()).padStart(2, '0');
            const minutes = String(date.getMinutes()).padStart(2, '0');
            return `${year}-${month}-${day}T${hours}:${minutes}`;
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
    </script>
</body>
</html>
