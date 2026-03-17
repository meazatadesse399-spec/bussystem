<?php
session_start();
include '../php/config.php';
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') header("Location: ../php/auth.php");

// Simple stats
$total_rev = $conn->query("SELECT SUM(total_amount) as s FROM bookings WHERE status='confirmed'")->fetch_assoc()['s'];
$total_bk = $conn->query("SELECT COUNT(*) as c FROM bookings")->fetch_assoc()['c'];
$top_route = $conn->query("SELECT CONCAT(d.station_name, ' -> ', a.station_name) as route, COUNT(*) as c FROM bookings b JOIN bus_schedules s ON b.schedule_id=s.id JOIN bus_stations d ON s.departure_station_id=d.id JOIN bus_stations a ON s.arrival_station_id=a.id GROUP BY route ORDER BY c DESC LIMIT 1")->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports</title>
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
                <a href="messages.php"><i class="fas fa-envelope"></i> Messages</a>
                <a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a>
            </div>
        </div>
        <div class="admin-content">
            <h1>System Reports</h1>
            <div class="admin-stats">
                <div class="admin-stat">
                    <h3>Total Revenue</h3>
                    <p>$<?php echo number_format($total_rev, 2); ?></p>
                </div>
                <div class="admin-stat">
                    <h3>Total Bookings</h3>
                    <p><?php echo $total_bk; ?></p>
                </div>
                <div class="admin-stat">
                    <h3>Top Route</h3>
                    <p style="font-size:1.2rem;"><?php echo $top_route ? $top_route['route'] : 'N/A'; ?></p>
                </div>
            </div>
            
            <div class="dashboard-section">
                <h2>Revenue Logic</h2>
                <p>Detailed breakdown reports coming soon...</p>
            </div>
        </div>
    </div>
</body>
</html>
