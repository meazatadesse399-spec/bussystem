<?php
session_start();
include 'config.php';

// Handle booking process
$step = isset($_GET['step']) ? $_GET['step'] : 'search';

// Check if user is logged in (only for seat selection and booking)
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;

if (!$is_logged_in && ($step == 'seats' || $step == 'payment')) {
    $current_url = urlencode($_SERVER['REQUEST_URI']);
    header("Location: auth.php?action=login&redirect=" . $current_url);
    exit();
}
// Handle search
$from = isset($_GET['from']) ? $_GET['from'] : '';
$to = isset($_GET['to']) ? $_GET['to'] : '';
$date = isset($_GET['date']) ? $_GET['date'] : '';

$schedule_id = isset($_GET['schedule_id']) ? $_GET['schedule_id'] : 0;

// Normalize date format for DB
$db_date = '';
if ($date) {
    $db_date = date('Y-m-d', strtotime($date));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['select_seats'])) {
        // Store selected seats in session
        $_SESSION['selected_seats'] = $_POST['seats'];
        $_SESSION['schedule_id'] = $_POST['schedule_id'];
        header("Location: booking.php?step=payment");
        exit();
    }
    
    if (isset($_POST['process_payment'])) {
        // Process payment and create booking
        $schedule_id = $_SESSION['schedule_id'];
        $seats = $_SESSION['selected_seats'];
        $payment_method = $_POST['payment_method'];
        
        // Get schedule details
        $sql = "SELECT price FROM bus_schedules WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $schedule = $result->fetch_assoc();
        
        // Calculate total
        $seat_count = count(explode(',', $seats));
        $total_amount = $schedule['price'] * $seat_count;
        
        // Generate booking number
        $booking_number = 'FB' . date('Ymd') . strtoupper(substr(md5(uniqid()), 0, 6));
        
        // Create booking
        $insert_sql = "INSERT INTO bookings (booking_number, user_id, schedule_id, seat_numbers, total_amount, status, payment_status) 
                      VALUES (?, ?, ?, ?, ?, 'confirmed', 'paid')";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("siisd", $booking_number, $user_id, $schedule_id, $seats, $total_amount);
        
        if ($stmt->execute()) {
            $booking_id = $stmt->insert_id;
            
            // Update available seats
            $update_sql = "UPDATE bus_schedules SET available_seats = available_seats - ? WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("ii", $seat_count, $schedule_id);
            $stmt->execute();
            
            // Clear session data
            unset($_SESSION['selected_seats']);
            unset($_SESSION['schedule_id']);
            
            // Redirect to ticket
            header("Location: booking.php?step=ticket&id=" . $booking_id);
            exit();
        }
    }
}

// Get available buses based on search
$buses = [];
$search_performed = ($from && $to && $date);
$showing_fallback = false;

if ($search_performed) {
    $sql = "SELECT bs.*, b.bus_name, b.bus_number, b.image_url,
                   dep.station_name as departure_station,
                   arr.station_name as arrival_station
            FROM bus_schedules bs
            JOIN buses b ON bs.bus_id = b.id
            JOIN bus_stations dep ON bs.departure_station_id = dep.id
            JOIN bus_stations arr ON bs.arrival_station_id = arr.id
            WHERE DATE(bs.departure_time) = ? 
            AND bs.departure_station_id = ? 
            AND bs.arrival_station_id = ?
            AND bs.available_seats > 0
            ORDER BY bs.departure_time";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sii", $db_date, $from, $to);
    $stmt->execute();
    $result = $stmt->get_result();
    $buses = $result->fetch_all(MYSQLI_ASSOC);
    
    // Fallback: If no exact route match, show all buses for that date
    if (empty($buses) && $date) {
        $fallback_sql = "SELECT bs.*, b.bus_name, b.bus_number, b.image_url,
                       dep.station_name as departure_station,
                       arr.station_name as arrival_station
                FROM bus_schedules bs
                JOIN buses b ON bs.bus_id = b.id
                JOIN bus_stations dep ON bs.departure_station_id = dep.id
                JOIN bus_stations arr ON bs.arrival_station_id = arr.id
                WHERE DATE(bs.departure_time) = ? 
                AND bs.available_seats > 0
                ORDER BY bs.departure_time LIMIT 5";
        $stmt = $conn->prepare($fallback_sql);
        $stmt->bind_param("s", $db_date);
        $stmt->execute();
        $buses = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        if (!empty($buses)) $showing_fallback = true;
    }
}

// Get schedule details for seat selection
$schedule_details = [];
if ($schedule_id) {
    $sql = "SELECT bs.*, b.bus_name, b.bus_number, b.total_seats,
                   dep.station_name as departure_station,
                   arr.station_name as arrival_station
            FROM bus_schedules bs
            JOIN buses b ON bs.bus_id = b.id
            JOIN bus_stations dep ON bs.departure_station_id = dep.id
            JOIN bus_stations arr ON bs.arrival_station_id = arr.id
            WHERE bs.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedule_details = $result->fetch_assoc();
}

// Get booked seats for this schedule
$booked_seats = [];
if ($schedule_id) {
    $sql = "SELECT seat_numbers FROM bookings WHERE schedule_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $seats = explode(',', $row['seat_numbers']);
        $booked_seats = array_merge($booked_seats, $seats);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Tickets - Fiker Bus</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <nav class="dashboard-nav">
        <div class="container">
            <div class="nav-brand">
                <h2><i class="fas fa-bus"></i> Fiker</h2>
            </div>
            <div class="nav-menu">
                <a href="../dashboard/traveler.php"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                <a href="../php/auth.php?action=logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>

    <div class="payment-container">
        <!-- Booking Steps -->
        <div class="payment-steps">
            <?php 
            // Resolve schedule_id properly for navigation
            $nav_schedule_id = $schedule_id;
            if (!$nav_schedule_id && isset($_SESSION['schedule_id'])) {
                $nav_schedule_id = $_SESSION['schedule_id'];
            }
            // If on ticket page, try to get from ticket data if available
            if ($step == 'ticket' && isset($ticket)) {
                $nav_schedule_id = $ticket['schedule_id'];
            }
            
            // Check if seats are selected for payment step clickability
            $has_seats = isset($_SESSION['selected_seats']) && !empty($_SESSION['selected_seats']);
            // Check if we have a booking id for ticket step
            $current_booking_id = isset($_GET['id']) ? $_GET['id'] : null;
            ?>
            
            <?php if ($step == 'search'): ?>
                <div class="step active">
                    <div class="step-number">1</div>
                    <div class="step-label">Search Buses</div>
                </div>
            <?php else: ?>
                <a href="booking.php?step=search" class="step completed clickable" title="Go back to search">
                    <div class="step-number">1</div>
                    <div class="step-label">Search Buses</div>
                </a>
            <?php endif; ?>
            
            <?php if ($step == 'seats'): ?>
                <div class="step active">
                    <div class="step-number">2</div>
                    <div class="step-label">Select Seats</div>
                </div>
            <?php elseif ($nav_schedule_id): ?>
                <a href="booking.php?step=seats&schedule_id=<?php echo $nav_schedule_id; ?>" class="step completed clickable" title="Go to seat selection">
                    <div class="step-number">2</div>
                    <div class="step-label">Select Seats</div>
                </a>
            <?php else: ?>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-label">Select Seats</div>
                </div>
            <?php endif; ?>
            
            <?php if ($step == 'payment'): ?>
                <div class="step active">
                    <div class="step-number">3</div>
                    <div class="step-label">Payment</div>
                </div>
            <?php elseif ($has_seats): ?>
                <a href="booking.php?step=payment" class="step completed clickable" title="Go to payment">
                    <div class="step-number">3</div>
                    <div class="step-label">Payment</div>
                </a>
            <?php else: ?>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-label">Payment</div>
                </div>
            <?php endif; ?>
            
            <?php if ($step == 'ticket'): ?>
                <div class="step active">
                    <div class="step-number">4</div>
                    <div class="step-label">Get Ticket</div>
                </div>
            <?php elseif ($current_booking_id): ?>
                <a href="booking.php?step=ticket&id=<?php echo $current_booking_id; ?>" class="step completed clickable" title="View ticket">
                    <div class="step-number">4</div>
                    <div class="step-label">Get Ticket</div>
                </a>
            <?php else: ?>
                <div class="step">
                    <div class="step-number">4</div>
                    <div class="step-label">Get Ticket</div>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($step == 'search'): ?>
            <!-- Search Form -->
            <div class="booking-step-card">
                <div class="step-header">
                    <div class="step-number active">1</div>
                    <h2>Search Available Buses</h2>
                    <p class="step-description">Find the perfect bus for your journey by selecting your route and travel date</p>
                </div>
                
                <form method="GET" action="booking.php" class="search-form-modern">
                    <input type="hidden" name="step" value="search">
                    
                    <?php 
                    // Fetch all stations for dropdowns
                    $stations_result = $conn->query("SELECT * FROM bus_stations ORDER BY station_name");
                    $all_stations = [];
                    while($row = $stations_result->fetch_assoc()) {
                        $all_stations[] = $row;
                    }
                    ?>
                    
                    <div class="form-row-modern">
                        <div class="form-group-modern">
                            <div class="input-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <label for="from">Departure City</label>
                            <select id="from" name="from" required class="styled-select">
                                <option value="">Select departure city</option>
                                <?php foreach($all_stations as $station): ?>
                                    <option value="<?php echo $station['id']; ?>" <?php echo $from == $station['id'] ? 'selected' : ''; ?>>
                                        <?php echo $station['city']; ?> (<?php echo $station['station_name']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="arrow-icon">
                            <i class="fas fa-long-arrow-alt-right"></i>
                        </div>
                        
                        <div class="form-group-modern">
                            <div class="input-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <label for="to">Destination City</label>
                            <select id="to" name="to" required class="styled-select">
                                <option value="">Select destination city</option>
                                <?php foreach($all_stations as $station): ?>
                                    <option value="<?php echo $station['id']; ?>" <?php echo $to == $station['id'] ? 'selected' : ''; ?>>
                                        <?php echo $station['city']; ?> (<?php echo $station['station_name']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group-modern">
                            <div class="input-icon">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <label for="date">Travel Date</label>
                            <input type="date" id="date" name="date" 
                                   value="<?php echo $date ? $date : date('Y-m-d'); ?>" 
                                   required class="styled-input"
                                   min="<?php echo date('Y-m-d'); ?>">
                        </div>
                    </div>
                    
                    <div class="search-action">
                        <button type="submit" class="btn-search-bus">
                            <i class="fas fa-search"></i>
                            <span class="btn-text">Search Available Buses</span>
                            <span class="btn-subtext">Find your perfect ride</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Available Buses -->
            <?php if (!empty($buses)): ?>
                <div class="booking-step-card">
                    <div class="step-header">
                        <div class="step-number">2</div>
                        <h2>
                            <?php echo $showing_fallback ? 'Other Available Buses for Today' : 'Available Buses & Schedules'; ?>
                        </h2>
                        <?php if ($showing_fallback): ?>
                            <p class="step-description alert-warning" style="background: #fff3cd; color: #856404; padding: 10px; border-radius: 5px; margin-top: 10px;">
                                <i class="fas fa-info-circle"></i> No exact matches found for your route. Here are other comfortable buses traveling today.
                            </p>
                        <?php else: ?>
                            <p class="step-description">Choose from our comfortable buses with various departure times</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="buses-list-modern">
                        <?php foreach ($buses as $bus): ?>
                            <div class="bus-card-modern">
                                <div class="bus-card-header">
                                    <div class="bus-type">
                                        <i class="fas fa-bus"></i>
                                        <h3><?php echo $bus['bus_name']; ?></h3>
                                        <span class="bus-number"><?php echo $bus['bus_number']; ?></span>
                                    </div>
                                    <div class="bus-price">
                                        <span class="price">$<?php echo number_format($bus['price'], 2); ?></span>
                                        <span class="price-label">per seat</span>
                                    </div>
                                </div>
                                
                                <div class="bus-route-details">
                                    <div class="route-info">
                                        <div class="departure">
                                            <span class="time"><?php echo date('h:i A', strtotime($bus['departure_time'])); ?></span>
                                            <span class="station"><?php echo $bus['departure_station']; ?></span>
                                        </div>
                                        
                                        <div class="route-duration">
                                            <div class="duration-line">
                                                <i class="fas fa-circle start"></i>
                                                <div class="line"></div>
                                                <i class="fas fa-circle end"></i>
                                            </div>
                                            <span class="duration-text">
                                                <?php 
                                                    $diff = strtotime($bus['arrival_time']) - strtotime($bus['departure_time']);
                                                    echo floor($diff / 3600) . 'h ' . floor(($diff % 3600) / 60) . 'm';
                                                ?>
                                            </span>
                                        </div>
                                        
                                        <div class="arrival">
                                            <span class="time"><?php echo date('h:i A', strtotime($bus['arrival_time'])); ?></span>
                                            <span class="station"><?php echo $bus['arrival_station']; ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="bus-amenities">
                                    <div class="amenity">
                                        <i class="fas fa-chair"></i>
                                        <span><?php echo $bus['available_seats']; ?> seats left</span>
                                    </div>
                                    <div class="amenity">
                                        <i class="fas fa-wifi"></i>
                                        <span>Free WiFi</span>
                                    </div>
                                    <div class="amenity">
                                        <i class="fas fa-snowflake"></i>
                                        <span>AC</span>
                                    </div>
                                </div>
                                
                                <div class="bus-actions">
                                    <?php if ($is_logged_in): ?>
                                            <a href="booking.php?step=seats&schedule_id=<?php echo $bus['id']; ?>" 
                                               class="btn-select-bus">
                                                <i class="fas fa-chair"></i>
                                                <span class="btn-text">Select Seats</span>
                                                <span class="btn-subtext">Choose your preferred seats</span>
                                            </a>
                                        <?php else: ?>
                                            <a href="auth.php?action=login&redirect=<?php echo urlencode('booking.php?step=seats&schedule_id='.$bus['id']); ?>" class="btn-select-bus">
                                                <i class="fas fa-sign-in-alt"></i>
                                                <span class="btn-text">Login to Book</span>
                                                <span class="btn-subtext">Sign in to choose seats</span>
                                            </a>
                                        <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php elseif ($from && $to && $date): ?>
                <div class="no-buses-found">
                    <div class="no-buses-icon">
                        <i class="fas fa-bus-slash"></i>
                    </div>
                    <h3>No Buses Available</h3>
                    <p>Sorry, we couldn't find any buses for the selected route and date.</p>
                    <a href="booking.php?step=search" class="btn-try-again">
                        <i class="fas fa-redo"></i>
                        Try Different Search
                    </a>
                </div>
            <?php endif; ?>

        <?php elseif ($step == 'seats' && $schedule_details): ?>
            <!-- Seat Selection -->
            <div class="dashboard-section">
                <h2>Select Your Seats</h2>
                <div class="seat-selection">
                    <div class="seat-map">
                        <div class="driver-side">
                            <i class="fas fa-user-tie"></i>
                            <p>Driver</p>
                        </div>
                        
                        <div class="seats-grid">
                            <?php
                            $total_seats = $schedule_details['total_seats'];
                            $seats_per_row = 4;
                            $rows = ceil($total_seats / $seats_per_row);
                            
                            for ($i = 1; $i <= $rows; $i++):
                                for ($j = 1; $j <= $seats_per_row; $j++):
                                    $seat_number = ($i - 1) * $seats_per_row + $j;
                                    if ($seat_number > $total_seats) break;
                                    
                                    $is_booked = in_array($seat_number, $booked_seats);
                                    $class = $is_booked ? 'booked' : 'available';
                                    ?>
                                    <div class="seat <?php echo $class; ?>" 
                                         data-seat="<?php echo $seat_number; ?>"
                                         onclick="selectSeat(<?php echo $seat_number; ?>)">
                                        <?php echo $seat_number; ?>
                                    </div>
                                <?php endfor; ?>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <form method="POST" action="booking.php?step=seats&schedule_id=<?php echo $schedule_id; ?>">
                        <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                        <input type="hidden" name="seats" id="selected-seats" value="">
                        
                        <div class="seat-legend">
                            <div class="legend-item">
                                <div class="seat available"></div>
                                <span>Available</span>
                            </div>
                            <div class="legend-item">
                                <div class="seat selected"></div>
                                <span>Selected</span>
                            </div>
                            <div class="legend-item">
                                <div class="seat booked"></div>
                                <span>Booked</span>
                            </div>
                        </div>
                        
                        <div class="selection-summary">
                            <h3>Selected Seats: <span id="seat-list">None</span></h3>
                            <p>Total Amount: $<span id="total-amount">0.00</span></p>
                        </div>
                        
                        <button type="submit" name="select_seats" class="btn-primary" id="proceed-btn" disabled>
                            Proceed to Payment
                        </button>
                    </form>
                </div>
            </div>

            <script>
                let selectedSeats = [];
                const pricePerSeat = <?php echo $schedule_details['price']; ?>;
                
                function selectSeat(seatNumber) {
                    const seatElement = document.querySelector(`.seat[data-seat="${seatNumber}"]`);
                    
                    if (seatElement.classList.contains('booked')) {
                        return;
                    }
                    
                    if (seatElement.classList.contains('selected')) {
                        // Deselect seat
                        seatElement.classList.remove('selected');
                        selectedSeats = selectedSeats.filter(seat => seat !== seatNumber);
                    } else {
                        // Select seat
                        seatElement.classList.add('selected');
                        selectedSeats.push(seatNumber);
                    }
                    
                    updateSelectionSummary();
                }
                
                function updateSelectionSummary() {
                    const seatList = document.getElementById('seat-list');
                    const totalAmount = document.getElementById('total-amount');
                    const selectedSeatsInput = document.getElementById('selected-seats');
                    const proceedBtn = document.getElementById('proceed-btn');
                    
                    if (selectedSeats.length > 0) {
                        seatList.textContent = selectedSeats.sort((a, b) => a - b).join(', ');
                        const total = selectedSeats.length * pricePerSeat;
                        totalAmount.textContent = total.toFixed(2);
                        selectedSeatsInput.value = selectedSeats.join(',');
                        proceedBtn.disabled = false;
                    } else {
                        seatList.textContent = 'None';
                        totalAmount.textContent = '0.00';
                        selectedSeatsInput.value = '';
                        proceedBtn.disabled = true;
                    }
                }
            </script>

        <?php elseif ($step == 'payment'): ?>
            <!-- Payment -->
            <div class="dashboard-section">
                <h2>Payment Details</h2>
                
                <?php
                if (!isset($_SESSION['selected_seats']) || !isset($_SESSION['schedule_id'])) {
                    echo '<p>Session expired. Please start over.</p>';
                    echo '<a href="booking.php?step=search" class="btn-primary">Start Over</a>';
                } else {
                    $seats = $_SESSION['selected_seats'];
                    $schedule_id = $_SESSION['schedule_id'];
                    
                    // Get schedule details
                    $sql = "SELECT bs.*, b.bus_name, dep.station_name as departure_station, 
                                   arr.station_name as arrival_station
                            FROM bus_schedules bs
                            JOIN buses b ON bs.bus_id = b.id
                            JOIN bus_stations dep ON bs.departure_station_id = dep.id
                            JOIN bus_stations arr ON bs.arrival_station_id = arr.id
                            WHERE bs.id = ?";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("i", $schedule_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $schedule = $result->fetch_assoc();
                    
                    $seat_count = count(explode(',', $seats));
                    $total_amount = $schedule['price'] * $seat_count;
                ?>
                
                <div class="booking-summary">
                    <h3>Booking Summary</h3>
                    <div class="summary-details">
                        <p><strong>Route:</strong> <?php echo $schedule['departure_station']; ?> → <?php echo $schedule['arrival_station']; ?></p>
                        <p><strong>Bus:</strong> <?php echo $schedule['bus_name']; ?></p>
                        <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($schedule['departure_time'])); ?></p>
                        <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($schedule['departure_time'])); ?></p>
                        <p><strong>Selected Seats:</strong> <?php echo $seats; ?></p>
                        <p><strong>Seat Price:</strong> $<?php echo number_format($schedule['price'], 2); ?></p>
                        <p><strong>Number of Seats:</strong> <?php echo $seat_count; ?></p>
                        <h3 class="total-amount">Total Amount: $<?php echo number_format($total_amount, 2); ?></h3>
                    </div>
                </div>
                
                <form method="POST" action="booking.php?step=payment">
                    <div class="payment-methods">
                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="credit_card" required>
                            <div class="method-content">
                                <i class="fas fa-credit-card"></i>
                                <h4>Credit/Debit Card</h4>
                                <p>Pay with Visa, MasterCard, or American Express</p>
                            </div>
                        </label>
                        
                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="mobile_banking">
                            <div class="method-content">
                                <i class="fas fa-mobile-alt"></i>
                                <h4>Mobile Banking</h4>
                                <p>CBE Birr, M-Birr, Telebirr</p>
                            </div>
                        </label>
                        
                        <label class="payment-method">
                            <input type="radio" name="payment_method" value="paypal">
                            <div class="method-content">
                                <i class="fab fa-paypal"></i>
                                <h4>PayPal</h4>
                                <p>Pay with your PayPal account</p>
                            </div>
                        </label>
                    </div>
                    
                    <div id="credit-card-details" style="display: none;">
                        <h3>Card Details</h3>
                        <div class="form-group">
                            <label for="card_number">Card Number</label>
                            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="expiry_date">Expiry Date</label>
                                <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY">
                            </div>
                            <div class="form-group">
                                <label for="cvv">CVV</label>
                                <input type="text" id="cvv" name="cvv" placeholder="123">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="card_name">Name on Card</label>
                            <input type="text" id="card_name" name="card_name" placeholder="John Doe">
                        </div>
                    </div>
                    
                    <button type="submit" name="process_payment" class="btn-primary">
                        <i class="fas fa-lock"></i> Pay Now
                    </button>
                </form>
                
                <script>
                    document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
                        radio.addEventListener('change', function() {
                            const cardDetails = document.getElementById('credit-card-details');
                            if (this.value === 'credit_card') {
                                cardDetails.style.display = 'block';
                            } else {
                                cardDetails.style.display = 'none';
                            }
                        });
                    });
                </script>
                
                <?php } ?>
            </div>

        <?php elseif ($step == 'ticket' && isset($_GET['id'])): ?>
            <!-- Ticket Display -->
            <?php
            $booking_id = $_GET['id'];
            $sql = "SELECT b.*, u.full_name, u.email, u.phone,
                           bs.departure_time, bs.arrival_time, bs.price,
                           dep.station_name as departure_station,
                           arr.station_name as arrival_station,
                           bus.bus_name, bus.bus_number
                    FROM bookings b
                    JOIN users u ON b.user_id = u.id
                    JOIN bus_schedules bs ON b.schedule_id = bs.id
                    JOIN bus_stations dep ON bs.departure_station_id = dep.id
                    JOIN bus_stations arr ON bs.arrival_station_id = arr.id
                    JOIN buses bus ON bs.bus_id = bus.id
                    WHERE b.id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $ticket = $result->fetch_assoc();
            ?>
            
            <div class="ticket-container">
                <div class="ticket-header">
                    <h1><i class="fas fa-bus"></i> Fiker Bus</h1>
                    <p>E-Ticket - Booking Confirmed</p>
                </div>
                
                <div class="ticket-body">
                    <div class="ticket-info">
                        <div class="info-group">
                            <h3>Booking Number</h3>
                            <p><?php echo $ticket['booking_number']; ?></p>
                        </div>
                        <div class="info-group">
                            <h3>Passenger Name</h3>
                            <p><?php echo $ticket['full_name']; ?></p>
                        </div>
                        <div class="info-group">
                            <h3>Bus Name</h3>
                            <p><?php echo $ticket['bus_name']; ?> (<?php echo $ticket['bus_number']; ?>)</p>
                        </div>
                        <div class="info-group">
                            <h3>Seat Numbers</h3>
                            <p><?php echo $ticket['seat_numbers']; ?></p>
                        </div>
                    </div>
                    
                    <div class="route-display">
                        <div class="station">
                            <h3>From</h3>
                            <p><?php echo $ticket['departure_station']; ?></p>
                            <p><?php echo date('h:i A', strtotime($ticket['departure_time'])); ?></p>
                            <p><?php echo date('F d, Y', strtotime($ticket['departure_time'])); ?></p>
                        </div>
                        
                        <div class="route-line"></div>
                        
                        <div class="station">
                            <h3>To</h3>
                            <p><?php echo $ticket['arrival_station']; ?></p>
                            <p><?php echo date('h:i A', strtotime($ticket['arrival_time'])); ?></p>
                            <p><?php echo date('F d, Y', strtotime($ticket['arrival_time'])); ?></p>
                        </div>
                    </div>
                    
                    <div class="ticket-info">
                        <div class="info-group">
                            <h3>Total Amount</h3>
                            <p>$<?php echo number_format($ticket['total_amount'], 2); ?></p>
                        </div>
                        <div class="info-group">
                            <h3>Booking Date</h3>
                            <p><?php echo date('F d, Y', strtotime($ticket['booking_date'])); ?></p>
                        </div>
                        <div class="info-group">
                            <h3>Status</h3>
                            <p><?php echo ucfirst($ticket['status']); ?></p>
                        </div>
                        <div class="info-group">
                            <h3>Payment Status</h3>
                            <p><?php echo ucfirst($ticket['payment_status']); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="ticket-footer">
                    <p>Please arrive at the bus station at least 30 minutes before departure</p>
                    <p>Present this ticket or booking number at the counter</p>
                    <div class="ticket-number"><?php echo $ticket['booking_number']; ?></div>
                    <button onclick="window.print()" class="btn-primary" style="margin-top: 15px;">
                        <i class="fas fa-print"></i> Print Ticket
                    </button>
                    <a href="../dashboard/traveler.php" class="btn-view" style="margin-top: 10px; display: inline-block;">
                        <i class="fas fa-home"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        <?php endif; ?>
    <script src="../js/dashboard.js"></script>
    <script src="../js/payment.js"></script>
</body>
</html>