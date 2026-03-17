<?php
session_start();
include '../php/config.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: ../php/auth.php?action=login");
    exit();
}

// Get admin stats
$stats = [];
$queries = [
    'total_bookings' => "SELECT COUNT(*) as count FROM bookings",
    'total_users' => "SELECT COUNT(*) as count FROM users WHERE user_type = 'traveler'",
    'today_bookings' => "SELECT COUNT(*) as count FROM bookings WHERE DATE(booking_date) = CURDATE()",
    'total_revenue' => "SELECT SUM(total_amount) as revenue FROM bookings WHERE payment_status = 'paid'",
    'active_buses' => "SELECT COUNT(*) as count FROM bus_schedules WHERE departure_time > NOW() AND available_seats > 0",
    'pending_bookings' => "SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'"
];

foreach ($queries as $key => $sql) {
    $result = $conn->query($sql);
    $stats[$key] = $result->fetch_assoc();
}

// Sort logic
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'booking_date';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$new_order = ($order == 'ASC') ? 'DESC' : 'ASC';

// Map display column to DB column for admin
$sort_db = $sort;
if ($sort == 'total_amount') $sort_db = 'b.total_amount';
if ($sort == 'booking_number') $sort_db = 'b.booking_number';
if ($sort == 'departure_time') $sort_db = 'bs.departure_time';

// Get recent bookings
$recent_sql = "SELECT b.*, u.full_name, bs.departure_time, 
               dep.station_name as departure_station,
               arr.station_name as arrival_station
               FROM bookings b
               JOIN users u ON b.user_id = u.id
               JOIN bus_schedules bs ON b.schedule_id = bs.id
               JOIN bus_stations dep ON bs.departure_station_id = dep.id
               JOIN bus_stations arr ON bs.arrival_station_id = arr.id
               ORDER BY $sort_db $order LIMIT 10";
$recent_result = $conn->query($recent_sql);

// Get upcoming schedules
$upcoming_sql = "SELECT bs.*, b.bus_name, b.bus_number,
                 dep.station_name as departure_station,
                 arr.station_name as arrival_station
                 FROM bus_schedules bs
                 JOIN buses b ON bs.bus_id = b.id
                 JOIN bus_stations dep ON bs.departure_station_id = dep.id
                 JOIN bus_stations arr ON bs.arrival_station_id = arr.id
                 WHERE bs.departure_time > NOW()
                 ORDER BY bs.departure_time ASC LIMIT 10";
$upcoming_result = $conn->query($upcoming_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Fiker Bus</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="admin-sidebar">
            <div class="admin-brand">
                <h2><i class="fas fa-bus"></i> Fiker Admin</h2>
                <p style="color: #bdc3c7; font-size: 0.9rem;">Welcome, <?php echo $_SESSION['username']; ?></p>
            </div>
            
            <div class="admin-menu">
                <a href="admin.php" class="active">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="bookings.php">
                    <i class="fas fa-ticket-alt"></i> Manage Bookings
                </a>
                <a href="buses.php">
                    <i class="fas fa-bus"></i> Manage Buses
                </a>
                <a href="schedules.php">
                    <i class="fas fa-calendar-alt"></i> Manage Schedules
                </a>
                <a href="stations.php">
                    <i class="fas fa-map-marker-alt"></i> Manage Stations
                </a>
                <a href="../index.php#routes" target="_blank">
                    <i class="fas fa-route"></i> Live Routes
                </a>
                <a href="users.php">
                    <i class="fas fa-users"></i> Manage Users
                </a>
                <a href="reports.php">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
                <a href="messages.php">
                    <i class="fas fa-envelope"></i> Messages
                </a>
                <a href="../php/auth.php?action=logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="admin-content">
            <div class="dashboard-header">
                <h1>Admin Dashboard</h1>
                <p>Overview of your bus management system</p>
            </div>

            <!-- Stats -->
            <div class="admin-stats">
                <a href="bookings.php" class="admin-stat">
                    <h3>Total Bookings</h3>
                    <p><?php echo $stats['total_bookings']['count']; ?></p>
                </a>
                <a href="users.php" class="admin-stat">
                    <h3>Total Users</h3>
                    <p><?php echo $stats['total_users']['count']; ?></p>
                </a>
                <a href="bookings.php" class="admin-stat">
                    <h3>Today's Bookings</h3>
                    <p><?php echo $stats['today_bookings']['count']; ?></p>
                </a>
                <a href="reports.php" class="admin-stat">
                    <h3>Total Revenue</h3>
                    <p>$<?php echo number_format($stats['total_revenue']['revenue'] ?: 0, 2); ?></p>
                </a>
                <a href="buses.php" class="admin-stat">
                    <h3>Active Buses</h3>
                    <p><?php echo $stats['active_buses']['count']; ?></p>
                </a>
                <a href="bookings.php" class="admin-stat">
                    <h3>Pending Bookings</h3>
                    <p><?php echo $stats['pending_bookings']['count']; ?></p>
                </a>
            </div>

            <!-- Recent Bookings -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Recent Bookings</h2>
                    <a href="bookings.php" class="btn-view-all">View All →</a>
                </div>
                
                <div class="bookings-table">
                    <?php if ($recent_result->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th><a href="?sort=booking_number&order=<?php echo $new_order; ?>">Booking # <i class="fas fa-sort"></i></a></th>
                                    <th>Passenger</th>
                                    <th>Route</th>
                                    <th><a href="?sort=departure_time&order=<?php echo $new_order; ?>">Date <i class="fas fa-sort"></i></a></th>
                                    <th><a href="?sort=total_amount&order=<?php echo $new_order; ?>">Amount <i class="fas fa-sort"></i></a></th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $recent_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><a href="../php/booking.php?step=ticket&id=<?php echo $booking['id']; ?>" class="booking-link"><?php echo $booking['booking_number']; ?></a></td>
                                        <td><a href="users.php?id=<?php echo $booking['user_id']; ?>" class="booking-link"><?php echo $booking['full_name']; ?></a></td>
                                        <td><a href="../php/booking.php?step=ticket&id=<?php echo $booking['id']; ?>" class="booking-link"><?php echo $booking['departure_station'] . ' → ' . $booking['arrival_station']; ?></a></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($booking['departure_time'])); ?></td>
                                        <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td class="action-buttons">
                                            <button onclick="updateBooking(<?php echo $booking['id']; ?>, 'confirmed')" 
                                                    class="btn-confirm" <?php echo $booking['status'] == 'confirmed' ? 'disabled' : ''; ?> title="Confirm Booking">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button onclick="updateBooking(<?php echo $booking['id']; ?>, 'cancelled')" 
                                                    class="btn-cancel" <?php echo $booking['status'] == 'cancelled' ? 'disabled' : ''; ?> title="Cancel Booking">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <a href="../php/booking.php?step=ticket&id=<?php echo $booking['id']; ?>" class="btn-view" title="View Ticket">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-ticket-alt"></i>
                            <h3>No Bookings Yet</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Upcoming Schedules -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Upcoming Bus Schedules</h2>
                    <a href="schedules.php" class="btn-view-all">View All →</a>
                </div>
                
                <div class="bookings-table">
                    <?php if ($upcoming_result->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Bus</th>
                                    <th>Route</th>
                                    <th>Departure</th>
                                    <th>Arrival</th>
                                    <th>Available Seats</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($schedule = $upcoming_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $schedule['bus_name']; ?> (<?php echo $schedule['bus_number']; ?>)</td>
                                        <td><?php echo $schedule['departure_station'] . ' → ' . $schedule['arrival_station']; ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($schedule['departure_time'])); ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($schedule['arrival_time'])); ?></td>
                                        <td><?php echo $schedule['available_seats']; ?></td>
                                        <td>$<?php echo number_format($schedule['price'], 2); ?></td>
                                        <td>
                                            <?php if ($schedule['available_seats'] == 0): ?>
                                                <span class="status-badge" style="background-color: #dc3545; color: white;">Full</span>
                                            <?php elseif ($schedule['available_seats'] < 10): ?>
                                                <span class="status-badge" style="background-color: #ffc107; color: black;">Almost Full</span>
                                            <?php else: ?>
                                                <span class="status-badge" style="background-color: #28a745; color: white;">Available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h3>No Upcoming Schedules</h3>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateBooking(bookingId, status) {
            if (confirm('Are you sure you want to ' + status + ' this booking?')) {
                fetch('../php/update_booking.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        booking_id: bookingId,
                        status: status
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Booking updated successfully!');
                        location.reload();
                    } else {
                        alert('Error updating booking: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                });
            }
        }
    </script>
</body>
</html>