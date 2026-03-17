<?php
include 'php/config.php';

// Fetch all stations for search dropdowns
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
               ORDER BY id LIMIT 100";
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
    <title>Fiker Bus - Your Comfortable Journey Starts Here</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <div class="logo">
                <h1><i class="fas fa-bus"></i> Fiker</h1>
            </div>
            <div class="nav-links">
                <a href="#home">Home</a>
                <a href="#about">About</a>
                <a href="#services">Services</a>
                <a href="#routes">Routes</a>
                <a href="#contact">Contact</a>
                <a href="php/auth.php?action=login" class="btn-login">Login</a>
                <a href="php/auth.php?action=signup" class="btn-signup">Sign Up</a>
            </div>
            <div class="mobile-menu">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="container">
            <div class="hero-content">
                <h2>Travel With Comfort & Safety</h2>
                <p>Book your bus tickets online with Fiker. Enjoy comfortable rides across the country with our
                    modern fleet.</p>
                <a href="php/auth.php?action=signup" class="btn-primary">Book Your Ticket Now</a>
            </div>
        </div>
    </section>

    <!-- Search Section -->
    <section class="search-section">
        <div class="container">
            <div class="search-box">
                <h3>Find Your Bus</h3>
                <form action="php/booking.php" method="GET">
                    <div class="form-group">
                        <label for="from"><i class="fas fa-map-marker-alt"></i> From</label>
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
                        <label for="to"><i class="fas fa-map-marker-alt"></i> To</label>
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
                        <label for="date"><i class="fas fa-calendar-alt"></i> Date</label>
                        <input type="date" id="date" name="date" required value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <button type="submit" class="btn-search">
                        <i class="fas fa-search"></i> Search Buses
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="about" id="about">
        <div class="container">
            <h2 class="section-title">Why Choose Fiker?</h2>
            <div class="features">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h3>Safe Travel</h3>
                    <p>Our buses are regularly maintained and drivers are highly trained for your safety.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-couch"></i>
                    </div>
                    <h3>Comfortable Seats</h3>
                    <p>Enjoy comfortable reclining seats with ample legroom for a relaxing journey.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-wifi"></i>
                    </div>
                    <h3>Free WiFi</h3>
                    <p>Stay connected with our complimentary high-speed WiFi on all buses.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h3>On-time Service</h3>
                    <p>We pride ourselves on punctuality with over 95% on-time departure rate.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Bus Gallery -->
    <section class="gallery" id="services">
        <div class="container">
            <h2 class="section-title">Our Modern Fleet</h2>
            <div class="gallery-grid">
                <div class="gallery-item">
                    <img src="assets/images/bus1.png" alt="Luxury Bus">
                    <div class="gallery-overlay">
                        <h3>Luxury Coach</h3>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="assets/images/bus2.png" alt="Sleeper Bus">
                    <div class="gallery-overlay">
                        <h3>Sleeper Bus</h3>
                    </div>
                </div>
                <div class="gallery-item">
                    <img src="assets/images/bus3.png" alt="Executive Bus">
                    <div class="gallery-overlay">
                        <h3>Executive Class</h3>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Routes Section -->
    <section class="routes" id="routes">
        <div class="container">
            <h2 class="section-title">Explore All Our Routes</h2>
            
            <div class="route-search-filter" style="max-width: 600px; margin: 0 auto 30px; position: relative;">
                <input type="text" id="routeSearch" placeholder="Search by city (e.g. Addis Ababa, Bahir Dar...)" 
                       style="width: 100%; padding: 15px 50px 15px 20px; border-radius: 30px; border: 2px solid #e9ecef; font-size: 1.1rem; outline: none; transition: border-color 0.3s;">
                <i class="fas fa-search" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); color: #bdc3c7;"></i>
            </div>

            <div class="routes-grid" id="routesGrid">
                <?php if (count($popular_routes) > 0): ?>
                    <?php foreach ($popular_routes as $route): ?>
                        <div class="route-card">
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
                                <a href="php/booking.php?from=<?php echo $route['departure_station_id']; ?>&to=<?php echo $route['arrival_station_id']; ?>&date=<?php echo date('Y-m-d'); ?>" class="btn-book">
                                    Book Now
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-routes">No active routes available at the moment. Please check back later.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section class="contact" id="contact">
        <div class="container">
            <h2 class="section-title">Contact Us</h2>
            <div class="contact-container">
                <div class="contact-info">
                    <h3>Get in Touch</h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <h4>Visit Us</h4>
                                <p>Meskel Square, Addis Ababa, Ethiopia</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <h4>Call Anytime</h4>
                                <p>+251 911 234 567</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <h4>Email Us</h4>
                                <p>info@fikkerbus.com</p>
                            </div>
                        </div>
                        <div class="info-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <h4>Support Hours</h4>
                                <p>24/7 Customer Support</p>
                            </div>
                        </div>
                    </div>
                    <div class="contact-socials">
                        <a href="#"><i class="fab fa-facebook-f"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3><i class="fas fa-bus"></i> Fiker</h3>
                    <p>Your comfort is our priority. Travel safely with Fiker across Ethiopia.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <a href="#home">Home</a>
                    <a href="#about">About</a>
                    <a href="#services">Services</a>
                    <a href="#routes">Routes</a>
                    <a href="#contact">Contact</a>
                </div>
                <div class="footer-section">
                    <h3>Follow Us</h3>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-telegram"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2023 Fiker Bus. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="js/main.js"></script>
</body>

</html>
