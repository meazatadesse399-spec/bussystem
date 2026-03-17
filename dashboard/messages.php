<?php
session_start();
include '../php/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') header("Location: ../php/auth.php");

// Mark as read if viewed (simplified: just list for now)
if (isset($_POST['delete'])) {
    $conn->query("DELETE FROM contact_messages WHERE id=" . $_POST['delete']);
}

$msgs = $conn->query("SELECT * FROM contact_messages ORDER BY submitted_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <div class="admin-sidebar">
            <div class="admin-brand"><h2><i class="fas fa-bus"></i> Fiker Admin</h2></div>
            <div class="admin-menu">
                <a href="admin.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="bookings.php"><i class="fas fa-ticket-alt"></i> Bookings</a>
                <a href="buses.php"><i class="fas fa-bus"></i> Buses</a>
                <a href="schedules.php"><i class="fas fa-calendar-alt"></i> Schedules</a>
                <a href="stations.php"><i class="fas fa-map-marker-alt"></i> Stations</a>
                <a href="users.php"><i class="fas fa-users"></i> Users</a>
                <a href="messages.php" class="active"><i class="fas fa-envelope"></i> Messages</a>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            </div>
        </div>
        <div class="admin-content">
            <h1>Messages</h1>
            <div class="bookings-table">
                <table>
                    <thead><tr><th>From</th><th>Subject</th><th>Message</th><th>Date</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($r=$msgs->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $r['name']; ?><br><small><?php echo $r['email']; ?></small></td>
                            <td><?php echo $r['subject']; ?></td>
                            <td><?php echo $r['message']; ?></td>
                            <td><?php echo $r['submitted_at']; ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Delete?')">
                                    <input type="hidden" name="delete" value="<?php echo $r['id']; ?>">
                                    <button class="btn-view" style="background:#e74c3c">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
