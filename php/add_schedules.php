<?php
include 'config.php';

// Route definitions (From -> To -> Duration in Hours -> Price)
$routes = [
    // From Addis Ababa (1)
    [1, 13, 1.5, 10.00], // Addis -> Adama
    [1, 10, 3.0, 15.00], // Addis -> Wolkite
    [1, 12, 4.0, 18.00], // Addis -> Hossana
    [1, 11, 5.0, 20.00], // Addis -> Halaba
    [1, 4, 5.0, 22.00],  // Addis -> Hawassa (Update)
    [1, 9, 8.0, 30.00],  // Addis -> Arba Minch

    // Returns
    [13, 1, 1.5, 10.00], // Adama -> Addis
    [10, 1, 3.0, 15.00], // Wolkite -> Addis
    [12, 1, 4.0, 18.00], // Hossana -> Addis
    [11, 1, 5.0, 20.00], // Halaba -> Addis
    [9, 1, 8.0, 30.00],  // Arba Minch -> Addis

    // Inter-city
    [4, 9, 4.0, 15.00],  // Hawassa -> Arba Minch
    [9, 4, 4.0, 15.00],  // Arba Minch -> Hawassa
    [12, 11, 1.0, 5.00], // Hossana -> Halaba
    [11, 4, 1.5, 8.00],   // Halaba -> Hawassa
    
    // New Routes
    [1, 14, 6.0, 25.00], // Addis -> Desse
    [14, 1, 6.0, 25.00],
    
    [1, 15, 5.5, 22.00], // Addis -> Kombolcha
    [15, 1, 5.5, 22.00],
    
    [1, 16, 5.0, 20.00], // Addis -> Debre Markos
    [16, 1, 5.0, 20.00],
    
    [1, 17, 6.5, 28.00], // Addis -> Nekemte
    [17, 1, 6.5, 28.00],
    
    [1, 18, 4.0, 15.00], // Addis -> Shashemene
    [18, 1, 4.0, 15.00],
    
    [1, 19, 1.0, 8.00],  // Addis -> Bishoftu
    [19, 1, 1.0, 8.00],
    
    [14, 15, 0.5, 5.00], // Desse -> Kombolcha
    [18, 4, 0.5, 5.00],  // Shashemene -> Hawassa
    [13, 19, 0.5, 5.00]  // Adama -> Bishoftu
];

// Buses available (ID 1 to 5)
$buses = [1, 2, 3, 4, 5];

echo "<h3>Generating Schedules...</h3>";

$count = 0;
// Generate for next 7 days
for ($d = 0; $d < 7; $d++) {
    $date = date('Y-m-d', strtotime("+$d days"));
    
    foreach ($routes as $route) {
        $dep_station = $route[0];
        $arr_station = $route[1];
        $duration = $route[2];
        $price = $route[3];
        
        // Create 2 trips per day per route (Morning and Afternoon)
        $times = ['06:00:00', '13:00:00'];
        
        foreach ($times as $time) {
            $dep_datetime = "$date $time";
            $arr_datetime = date('Y-m-d H:i:s', strtotime($dep_datetime) + ($duration * 3600));
            
            // Random bus assignment
            $bus_id = $buses[array_rand($buses)];
            
            // Get total seats for this bus (simple lookup or hardcode for speed)
            // Ideally we query DB but let's assume average 40 for bulk insert
            $total_seats = 40; 
            if ($bus_id == 2) $total_seats = 30; // Sleeper
            if ($bus_id == 3) $total_seats = 35; // Executive
            
            $sql = "INSERT INTO bus_schedules (bus_id, departure_station_id, arrival_station_id, departure_time, arrival_time, price, available_seats) 
                    VALUES ($bus_id, $dep_station, $arr_station, '$dep_datetime', '$arr_datetime', $price, $total_seats)";
            
            if ($conn->query($sql)) {
                $count++;
            }
        }
    }
}

echo "Successfully added $count new schedules for the next 7 days.<br>";
echo "<a href='../index.php'>Go Back Home</a>";
?>
