<?php
session_start();
include '../php/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../php/auth.php?action=login");
    exit();
}

$message = '';

// Handle Add/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_bus'])) {
        $number = $_POST['bus_number'];
        $name = $_POST['bus_name'];
        $seats = $_POST['total_seats'];
        $amenities = $_POST['amenities'];
        // Image handling simplified - using default or text
        $image = 'bus1.png'; // Default
        
        $stmt = $conn->prepare("INSERT INTO buses (bus_number, bus_name, total_seats, amenities, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("ssiss", $number, $name, $seats, $amenities, $image);
        if ($stmt->execute()) $message = "Bus added successfully!";
        else $message = "Error adding bus.";
    } elseif (isset($_POST['edit_bus'])) {
        $id = $_POST['bus_id'];
        $number = $_POST['bus_number'];
        $name = $_POST['bus_name'];
        $seats = $_POST['total_seats'];
        $amenities = $_POST['amenities'];
        
        $stmt = $conn->prepare("UPDATE buses SET bus_number = ?, bus_name = ?, total_seats = ?, amenities = ? WHERE id = ?");
        $stmt->bind_param("ssisi", $number, $name, $seats, $amenities, $id);
        if ($stmt->execute()) $message = "Bus updated successfully!";
        else $message = "Error updating bus.";
    } elseif (isset($_POST['delete_id'])) {
        $stmt = $conn->prepare("DELETE FROM buses WHERE id = ?");
        $stmt->bind_param("i", $_POST['delete_id']);
        if ($stmt->execute()) $message = "Bus deleted successfully!";
    }
}

$buses = $conn->query("SELECT * FROM buses");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Buses - Fiker Bus</title>
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
                <a href="buses.php" class="active"><i class="fas fa-bus"></i> Manage Buses</a>
                <a href="schedules.php"><i class="fas fa-calendar-alt"></i> Manage Schedules</a>
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
                <h1>Manage Buses</h1>
                <button onclick="document.getElementById('addModal').style.display='block'" class="btn-primary">Add New Bus</button>
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
                            <th>Number</th>
                            <th>Name</th>
                            <th>Seats</th>
                            <th>Amenities</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $buses->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['bus_number']; ?></td>
                            <td><?php echo $row['bus_name']; ?></td>
                            <td><?php echo $row['total_seats']; ?></td>
                            <td><?php echo $row['amenities']; ?></td>
                                <td class="action-buttons">
                                    <button type="button" class="btn-confirm" 
                                            onclick='editBus(<?php echo json_encode($row); ?>)' title="Edit Bus">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this bus?')">
                                        <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn-cancel" title="Delete Bus">
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

    <!-- Add Bus Modal -->
    <div id="addModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index: 2000;">
        <div style="background:white; width:400px; margin: 100px auto; padding:20px; border-radius:10px; box-shadow: 0 5px 25px rgba(0,0,0,0.2);">
            <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="margin:0;">Add New Bus</h2>
                <button type="button" onclick="closeModal('addModal')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <form method="POST">
                <div class="form-group">
                    <label>Bus Number</label>
                    <input type="text" name="bus_number" required>
                </div>
                <div class="form-group">
                    <label>Bus Name</label>
                    <input type="text" name="bus_name" required>
                </div>
                <div class="form-group">
                    <label>Total Seats</label>
                    <input type="number" name="total_seats" required>
                </div>
                <div class="form-group">
                    <label>Amenities</label>
                    <input type="text" name="amenities">
                </div>
                <div style="margin-top:20px; display:flex; gap:10px;">
                    <button type="submit" name="add_bus" class="btn-primary" style="flex:1;">Save Bus</button>
                    <button type="button" onclick="closeModal('addModal')" class="btn-cancel" style="flex:1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Bus Modal -->
    <div id="editModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index: 2000;">
        <div style="background:white; width:400px; margin: 100px auto; padding:20px; border-radius:10px; box-shadow: 0 5px 25px rgba(0,0,0,0.2);">
            <div class="modal-header" style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2 style="margin:0;">Edit Bus</h2>
                <button type="button" onclick="closeModal('editModal')" style="background:none; border:none; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="bus_id" id="edit_bus_id">
                <div class="form-group">
                    <label>Bus Number</label>
                    <input type="text" name="bus_number" id="edit_bus_number" required>
                </div>
                <div class="form-group">
                    <label>Bus Name</label>
                    <input type="text" name="bus_name" id="edit_bus_name" required>
                </div>
                <div class="form-group">
                    <label>Total Seats</label>
                    <input type="number" name="total_seats" id="edit_total_seats" required>
                </div>
                <div class="form-group">
                    <label>Amenities</label>
                    <input type="text" name="amenities" id="edit_amenities">
                </div>
                <div style="margin-top:20px; display:flex; gap:10px;">
                    <button type="submit" name="edit_bus" class="btn-primary" style="flex:1;">Update Bus</button>
                    <button type="button" onclick="closeModal('editModal')" class="btn-cancel" style="flex:1;">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editBus(bus) {
            document.getElementById('edit_bus_id').value = bus.id;
            document.getElementById('edit_bus_number').value = bus.bus_number;
            document.getElementById('edit_bus_name').value = bus.bus_name;
            document.getElementById('edit_total_seats').value = bus.total_seats;
            document.getElementById('edit_amenities').value = bus.amenities;
            document.getElementById('editModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target.className === 'modal-overlay') { // Not strictly used here but good practice
                closeModal('addModal');
                closeModal('editModal');
            }
        }
    </script>
</body>
</html>
