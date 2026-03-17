<?php
session_start();
include '../php/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../php/auth.php?action=login");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Handle Booking Actions (Cancel/Confirm) - Admin Only or User Cancel
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    $booking_id = $_POST['booking_id'];
    $action = $_POST['action'];
    
    if ($user_type == 'admin' || ($user_type == 'traveler' && $action == 'cancelled')) {
        $status = $action; // 'confirmed' or 'cancelled'
        $stmt = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $booking_id);
        $stmt->execute();
        
        // If cancelled, release seats (logic simplified for now)
        if ($status == 'cancelled') {
             $stmt = $conn->prepare("SELECT schedule_id, seat_numbers FROM bookings WHERE id = ?");
             $stmt->bind_param("i", $booking_id);
             $stmt->execute();
             $res = $stmt->get_result();
             if ($row = $res->fetch_assoc()) {
                 $count = count(explode(',', $row['seat_numbers']));
                 $upd = $conn->prepare("UPDATE bus_schedules SET available_seats = available_seats + ? WHERE id = ?");
                 $upd->bind_param("ii", $count, $row['schedule_id']);
                 $upd->execute();
             }
        }
        $success_msg = "Booking updated successfully ($action).";
    }
}

// Sort logic
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'booking_date';
$order = isset($_GET['order']) ? $_GET['order'] : 'DESC';
$new_order = ($order == 'ASC') ? 'DESC' : 'ASC';

// Map display column to DB column
$sort_db = $sort;
if ($sort == 'total_amount') $sort_db = 'b.total_amount';
if ($sort == 'booking_number') $sort_db = 'b.booking_number';
if ($sort == 'departure_time') $sort_db = 'bs.departure_time';

// Fetch Bookings
if ($user_type == 'admin') {
    $where = "";
    $params = [];
    if (isset($_GET['user_id'])) {
        $where = " WHERE b.user_id = ?";
        $params[] = $_GET['user_id'];
    }
    
    $sql = "SELECT b.*, u.full_name, bs.departure_time, dep.station_name as dep_name, arr.station_name as arr_name 
            FROM bookings b 
            JOIN users u ON b.user_id = u.id 
            JOIN bus_schedules bs ON b.schedule_id = bs.id 
            JOIN bus_stations dep ON bs.departure_station_id = dep.id 
            JOIN bus_stations arr ON bs.arrival_station_id = arr.id 
            $where 
            ORDER BY $sort_db $order";
            
    if (!empty($params)) {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $params[0]);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($sql);
    }
} else {
    $sql = "SELECT b.*, bs.departure_time, dep.station_name as dep_name, arr.station_name as arr_name 
            FROM bookings b 
            JOIN bus_schedules bs ON b.schedule_id = bs.id 
            JOIN bus_stations dep ON bs.departure_station_id = dep.id 
            JOIN bus_stations arr ON bs.arrival_station_id = arr.id 
            WHERE b.user_id = ? 
            ORDER BY $sort_db $order";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - Fiker Bus</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="dashboard-nav">
        <div class="container">
            <div class="nav-brand">
                <h2><i class="fas fa-bus"></i> Fiker Bus</h2>
            </div>
            <div class="nav-menu">
                <a href="<?php echo $user_type == 'admin' ? 'admin.php' : 'traveler.php'; ?>"><i class="fas fa-arrow-left"></i> Dashboard</a>
                <a href="../index.php#routes"><i class="fas fa-route"></i> Routes</a>
                <a href="../php/auth.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="container">
            <div class="dashboard-header">
                <h2><i class="fas fa-bus"></i> <?php echo $user_type == 'admin' ? 'Fiker Admin' : 'Fiker'; ?></h2>
            </div>

            <?php if (isset($success_msg)): ?>
                <div class="alert alert-success" style="padding: 15px; background: #d4edda; color: #155724; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                    <i class="fas fa-check-circle"></i> <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>

            <div class="bookings-table">
                <table>
                    <thead>
                        <tr>
                            <th><a href="?sort=booking_number&order=<?php echo $new_order; ?>">ID <i class="fas fa-sort"></i></a></th>
                            <?php if ($user_type == 'admin') echo '<th>User</th>'; ?>
                            <th>Route</th>
                            <th><a href="?sort=departure_time&order=<?php echo $new_order; ?>">Date <i class="fas fa-sort"></i></a></th>
                            <th>Seats</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <?php if ($user_type == 'traveler'): ?>
                                    <a href="../php/booking.php?step=ticket&id=<?php echo $row['id']; ?>" class="booking-link"><?php echo $row['booking_number']; ?></a>
                                <?php else: ?>
                                    <?php echo $row['booking_number']; ?>
                                <?php endif; ?>
                            </td>
                            <?php if ($user_type == 'admin') echo '<td>'.$row['full_name'].'</td>'; ?>
                            <td>
                                <?php if ($user_type == 'traveler'): ?>
                                    <a href="../php/booking.php?step=ticket&id=<?php echo $row['id']; ?>" class="booking-link"><?php echo $row['dep_name'] . ' -> ' . $row['arr_name']; ?></a>
                                <?php else: ?>
                                    <?php echo $row['dep_name'] . ' -> ' . $row['arr_name']; ?>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y h:i A', strtotime($row['departure_time'])); ?></td>
                            <td><?php echo $row['seat_numbers']; ?></td>
                            <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                            <td class="action-buttons">
                                <a href="../php/booking.php?step=ticket&id=<?php echo $row['id']; ?>" class="btn-view" title="View Ticket"><i class="fas fa-eye"></i> View</a>
                                <?php if ($row['status'] == 'pending' || ($user_type == 'admin' && $row['status'] != 'cancelled')): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="action" value="cancelled">
                                    <button type="submit" class="btn-cancel" onclick="return confirm('Cancel this booking?')" title="Cancel Booking"><i class="fas fa-times"></i> Cancel</button>
                                </form>
                                <?php endif; ?>
                                <?php if ($user_type == 'admin' && $row['status'] == 'pending'): ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="action" value="confirmed">
                                    <button type="submit" class="btn-confirm" title="Confirm Booking"><i class="fas fa-check"></i> Confirm</button>
                                </form>
                                <?php endif; ?>
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
