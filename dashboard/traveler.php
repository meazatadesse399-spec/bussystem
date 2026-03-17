<?php
session_start();
include '../php/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/auth.php?action=login");
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Handle Booking Cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'cancelled' && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    
    // Ensure the booking belongs to the current user
    $check_stmt = $conn->prepare("SELECT schedule_id, seat_numbers FROM bookings WHERE id = ? AND user_id = ?");
    $check_stmt->bind_param("ii", $booking_id, $user_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();
    
    if ($row = $check_res->fetch_assoc()) {
        $stmt = $conn->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $booking_id);
        if ($stmt->execute()) {
            // Release seats
            $count = count(explode(',', $row['seat_numbers']));
            $upd = $conn->prepare("UPDATE bus_schedules SET available_seats = available_seats + ? WHERE id = ?");
            $upd->bind_param("ii", $count, $row['schedule_id']);
            $upd->execute();
            $success_msg = "Booking cancelled successfully.";
        }
    }
}

// Sort logic
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'booking_date';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$new_order = ($order == 'ASC') ? 'DESC' : 'ASC';

// Get user's bookings
$bookings_sql = "SELECT b.*, bs.departure_time, bs.arrival_time, bs.price, 
                dep.station_name as departure_station, arr.station_name as arrival_station,
                bus.bus_name, bus.bus_number
                FROM bookings b
                JOIN bus_schedules bs ON b.schedule_id = bs.id
                JOIN bus_stations dep ON bs.departure_station_id = dep.id
                JOIN bus_stations arr ON bs.arrival_station_id = arr.id
                JOIN buses bus ON bs.bus_id = bus.id
                WHERE b.user_id = ? 
                ORDER BY $sort $order LIMIT 5";
$stmt = $conn->prepare($bookings_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$bookings_result = $stmt->get_result();

// Get all stations for search dropdowns
$stations_sql = "SELECT * FROM bus_stations ORDER BY station_name";
$stations_result = $conn->query($stations_sql);
$stations = [];
while ($row = $stations_result->fetch_assoc()) {
    $stations[] = $row;
}

// Fetch popular routes for the routes section
$routes_sql = "SELECT MIN(bs.id) as id, bs.departure_station_id, bs.arrival_station_id, 
               dep.city as from_city, dep.station_name as from_station,
               arr.city as to_city, arr.station_name as to_station,
               MIN(bs.price) as min_price
               FROM bus_schedules bs
               JOIN bus_stations dep ON bs.departure_station_id = dep.id
               JOIN bus_stations arr ON bs.arrival_station_id = arr.id
               WHERE bs.departure_time > NOW()
               GROUP BY bs.departure_station_id, bs.arrival_station_id
               ORDER BY id LIMIT 3";
$routes_result = $conn->query($routes_sql);
$popular_routes = [];
while ($row = $routes_result->fetch_assoc()) {
    $popular_routes[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler Dashboard - Fiker Bus</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Dashboard Navigation -->
    <nav class="dashboard-nav">
        <div class="container">
            <div class="nav-brand">
                <h2><i class="fas fa-bus"></i> Fiker</h2>
                <span class="user-welcome">Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</span>
            </div>
            <div class="nav-menu">
                <a href="traveler.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
                <a href="bookings.php"><i class="fas fa-ticket-alt"></i> My Bookings</a>
                <a href="#routes"><i class="fas fa-route"></i> Routes</a>
                <a href="profile.php"><i class="fas fa-user"></i> Profile</a>
                <a href="../php/auth.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <!-- Dashboard Main Content -->
    <div class="dashboard-container">
        <div class="container">
            <div class="dashboard-header">
                <h1>Traveler Dashboard</h1>
                <a href="../php/booking.php" class="btn-primary">
                    <i class="fas fa-plus"></i> Book New Ticket
                </a>
            </div>

            <?php if (isset($success_msg)): ?>
                <div class="alert alert-success" style="padding: 15px; background: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="quick-stats">
                <a href="bookings.php" class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-ticket-alt"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Total Bookings</h3>
                        <?php
                        $total_sql = "SELECT COUNT(*) as total FROM bookings WHERE user_id = ?";
                        $stmt = $conn->prepare($total_sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $total_result = $stmt->get_result();
                        $total = $total_result->fetch_assoc();
                        ?>
                        <p class="stat-number"><?php echo $total['total']; ?></p>
                    </div>
                </a>
                <a href="bookings.php" class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Confirmed</h3>
                        <?php
                        $confirmed_sql = "SELECT COUNT(*) as confirmed FROM bookings WHERE user_id = ? AND status = 'confirmed'";
                        $stmt = $conn->prepare($confirmed_sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $confirmed_result = $stmt->get_result();
                        $confirmed = $confirmed_result->fetch_assoc();
                        ?>
                        <p class="stat-number"><?php echo $confirmed['confirmed']; ?></p>
                    </div>
                </a>
                <a href="bookings.php" class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-info">
                        <h3>Upcoming Trips</h3>
                        <?php
                        $upcoming_sql = "SELECT COUNT(*) as upcoming FROM bookings b 
                                        JOIN bus_schedules bs ON b.schedule_id = bs.id
                                        WHERE b.user_id = ? AND b.status = 'confirmed' 
                                        AND bs.departure_time > NOW()";
                        $stmt = $conn->prepare($upcoming_sql);
                        $stmt->bind_param("i", $user_id);
                        $stmt->execute();
                        $upcoming_result = $stmt->get_result();
                        $upcoming = $upcoming_result->fetch_assoc();
                        ?>
                        <p class="stat-number"><?php echo $upcoming['upcoming']; ?></p>
                    </div>
                </a>
            </div>

            <!-- Recent Bookings -->
            <div class="dashboard-section">
                <div class="section-header">
                    <h2>Recent Bookings</h2>
                    <a href="bookings.php" class="btn-view-all">View All →</a>
                </div>
                
                <div class="bookings-table">
                    <?php if ($bookings_result->num_rows > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th><a href="?sort=booking_number&order=<?php echo $new_order; ?>">Booking # <i class="fas fa-sort"></i></a></th>
                                    <th>Route</th>
                                    <th>Bus</th>
                                    <th><a href="?sort=departure_time&order=<?php echo $new_order; ?>">Date <i class="fas fa-sort"></i></a></th>
                                    <th>Seats</th>
                                    <th><a href="?sort=total_amount&order=<?php echo $new_order; ?>">Amount <i class="fas fa-sort"></i></a></th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><a href="../php/booking.php?step=ticket&id=<?php echo $booking['id']; ?>" class="booking-link"><?php echo $booking['booking_number']; ?></a></td>
                                        <td><a href="../php/booking.php?step=ticket&id=<?php echo $booking['id']; ?>" class="booking-link"><?php echo $booking['departure_station'] . ' → ' . $booking['arrival_station']; ?></a></td>
                                        <td><?php echo $booking['bus_name']; ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($booking['departure_time'])); ?></td>
                                        <td><?php echo $booking['seat_numbers']; ?></td>
                                        <td>$<?php echo number_format($booking['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="../php/booking.php?step=ticket&id=<?php echo $booking['id']; ?>" class="btn-view" title="View Ticket">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if ($booking['status'] == 'pending'): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="action" value="cancelled">
                                                    <button type="submit" class="btn-cancel" onclick="return confirm('Are you sure you want to cancel this booking?')" title="Cancel Booking">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-ticket-alt"></i>
                            <h3>No Bookings Yet</h3>
                            <p>You haven't made any bookings yet. Start your journey with us!</p>
                            <a href="../php/booking.php" class="btn-primary">Book Your First Ticket</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Search -->
            <div class="dashboard-section">
                <h2>Find Your Next Trip</h2>
                <div class="quick-search">
                    <form action="../php/booking.php" method="GET">
                        <div class="search-row">
                            <div class="form-group">
                                <label for="from">From</label>
                                <select id="from" name="from" required>
                                    <option value="">Select departure</option>
                                    <?php foreach ($stations as $station): ?>
                                        <option value="<?php echo $station['id']; ?>">
                                            <?php echo $station['city']; ?> (<?php echo $station['station_name']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="to">To</label>
                                <select id="to" name="to" required>
                                    <option value="">Select destination</option>
                                    <?php foreach ($stations as $station): ?>
                                        <option value="<?php echo $station['id']; ?>">
                                            <?php echo $station['city']; ?> (<?php echo $station['station_name']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="date">Date</label>
                                <input type="date" id="date" name="date" required>
                            </div>
                            <div class="form-group">
                                <label for="passengers">Passengers</label>
                                <select id="passengers" name="passengers">
                                    <option value="1">1 Passenger</option>
                                    <option value="2">2 Passengers</option>
                                    <option value="3">3 Passengers</option>
                                    <option value="4">4 Passengers</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-search"></i> Search Buses
                        </button>
                    </form>
                </div>
            </div>

            <!-- Dashboard Routes Section -->
            <div class="dashboard-section" id="routes">
                <div class="section-header">
                    <h2>Our Popular Routes</h2>
                    <p>Choose a popular destination and book instantly.</p>
                </div>
                <div class="routes-grid">
                    <?php if (count($popular_routes) > 0): ?>
                        <?php foreach ($popular_routes as $route): ?>
                            <div class="route-card" style="margin-bottom: 20px;">
                                <div class="route-header">
                                    <div class="city-info">
                                        <h3><?php echo $route['from_city']; ?></h3>
                                        <p><?php echo $route['from_station']; ?></p>
                                    </div>
                                    <div class="route-arrow">
                                        <i class="fas fa-long-arrow-alt-right"></i>
                                    </div>
                                    <div class="city-info">
                                        <h3><?php echo $route['to_city']; ?></h3>
                                        <p><?php echo $route['to_station']; ?></p>
                                    </div>
                                </div>
                                <div class="route-footer">
                                    <div class="price-tag">
                                        <span>Starting from</span>
                                        <p>$<?php echo number_format($route['min_price'], 2); ?></p>
                                    </div>
                                    <a href="../php/booking.php?from=<?php echo $route['departure_station_id']; ?>&to=<?php echo $route['arrival_station_id']; ?>&date=<?php echo date('Y-m-d'); ?>" class="btn-book">
                                        Book Now
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-routes">No active routes available at the moment.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/dashboard.js"></script>
</body>
</html>