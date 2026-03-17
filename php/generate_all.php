<?php
include 'config.php';

// List of all station IDs
$stations = [1, 2, 3, 4, 5, 6, 7, 8, 12];
$buses = [1, 2, 3, 4, 5];

echo "<h3>Generating Universal Schedules...</h3>";

$count = 0;
// Generate for next 7 days
for ($d = 0; $d < 7; $d++) {
    $date = date('Y-m-d', strtotime("+$d days"));
    
    foreach ($stations as $from) {
        // Create a route to EVERY other station
        $to_list = array_filter($stations, function($id) use ($from) { return $id != $from; });
        
        foreach ($to_list as $to) {
            $times = ['08:00:00', '14:00:00'];
            foreach ($times as $time) {
                $dep_datetime = "$date $time";
                $bus_id = $buses[array_rand($buses)];
                $price = rand(20, 50);
                $seats = rand(20, 40);
                $arr_datetime = date('Y-m-d H:i:s', strtotime($dep_datetime) + (rand(3, 8) * 3600));
                
                $sql = "INSERT INTO bus_schedules (bus_id, departure_station_id, arrival_station_id, departure_time, arrival_time, price, available_seats) 
                        VALUES ($bus_id, $from, $to, '$dep_datetime', '$arr_datetime', $price, $seats)";
                
                if ($conn->query($sql)) {
                    $count++;
                }
            }
        }
    }
}

echo "Done! Generated $count schedules.";
?>
