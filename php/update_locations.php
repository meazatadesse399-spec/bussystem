<?php
include 'config.php';

// Array of new stations to check and add
$new_stations = [
    10 => ['Wolkite Bus Station', 'Wolkite', 'Wolkite Main Road', '+251113333333'],
    11 => ['Halaba Bus Station', 'Halaba', 'Halaba Kulito', '+251446666666'],
    12 => ['Hossana Bus Terminal', 'Hossana', 'Hossana City Center', '+251465555555'],
    13 => ['Adama Bus Station', 'Adama', 'Adama Express Terminal', '+251221111111'],
    14 => ['Desse Bus Terminal', 'Desse', 'Desse Piassa', '+251331111111'],
    15 => ['Kombolcha Station', 'Kombolcha', 'Kombolcha Industrial Park', '+251332222222'],
    16 => ['Debre Markos Station', 'Debre Markos', 'Debre Markos Center', '+251581111111'],
    17 => ['Nekemte Bus Station', 'Nekemte', 'Nekemte Main Road', '+251571111111'],
    18 => ['Shashemene Terminal', 'Shashemene', 'Shashemene Junction', '+251461111111'],
    19 => ['Bishoftu Bus Station', 'Bishoftu', 'Bishoftu Lake Side', '+251114444444']
];

foreach ($new_stations as $id => $data) {
    $name = $data[0];
    $city = $data[1];
    $addr = $data[2];
    $phone = $data[3];
    
    // Check if exists
    $check = $conn->query("SELECT id FROM bus_stations WHERE city = '$city'");
    if ($check->num_rows == 0) {
        $sql = "INSERT INTO bus_stations (id, station_name, city, address, contact_phone) VALUES ($id, '$name', '$city', '$addr', '$phone')";
        if ($conn->query($sql)) {
            echo "Added $city.<br>";
        } else {
            echo "Error adding $city: " . $conn->error . "<br>";
        }
    } else {
        echo "$city already exists.<br>";
    }
}

echo "<br><a href='../index.php'>Go Back Home</a>";
?>
