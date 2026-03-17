<?php
session_start();
include '../php/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') header("Location: ../php/auth.php");

if (isset($_POST['delete'])) {
    $conn->query("DELETE FROM users WHERE id=" . $_POST['delete']);
    $message = "User deleted successfully.";
}

$users = $conn->query("SELECT * FROM users WHERE user_type='traveler'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users</title>
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
                <a href="stations.php"><i class="fas fa-map-marker-alt"></i> Manage Stations</a>
                <a href="../index.php#routes" target="_blank"><i class="fas fa-route"></i> Live Routes</a>
                <a href="users.php" class="active"><i class="fas fa-users"></i> Manage Users</a>
                <a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
                <a href="../php/auth.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
        <div class="admin-content">
            <h1>Manage Users</h1>

            <?php if (isset($message) && $message): ?>
                <div class="alert alert-success" style="padding: 15px; background: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <div class="bookings-table">
                <table>
                    <thead><tr><th>Name</th><th>Username</th><th>Email</th><th>Phone</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($r=$users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $r['full_name']; ?></td>
                            <td><?php echo $r['username']; ?></td>
                            <td><?php echo $r['email']; ?></td>
                            <td><?php echo $r['phone']; ?></td>
                            <td class="action-buttons">
                                <a href="bookings.php?user_id=<?php echo $r['id']; ?>" class="btn-confirm" title="View Bookings">
                                    <i class="fas fa-ticket-alt"></i>
                                </a>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Delete User?')">
                                    <input type="hidden" name="delete" value="<?php echo $r['id']; ?>">
                                    <button type="submit" class="btn-cancel" title="Delete User">
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
</body>
</html>
