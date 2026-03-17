-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Generation Time: Jan 26, 2024 at 01:15 PM
-- Server version: 10.4.28-MariaDB
-- PHP Version: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `fikker_bus`
--
CREATE DATABASE IF NOT EXISTS `fikker_bus` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `fikker_bus`;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `user_type` enum('traveler','admin') DEFAULT 'traveler',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `full_name`, `phone`, `user_type`, `created_at`) VALUES
(1, 'admin', 'admin@fikkerbus.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', '+251911000000', 'admin', '2024-01-26 12:00:00'),
(2, 'john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Doe', '+251912345678', 'traveler', '2024-01-26 12:00:00'),
(3, 'sarah_m', 'sarah@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Smith', '+251913456789', 'traveler', '2024-01-26 12:00:00'),
(4, 'mike_t', 'mike@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mike Johnson', '+251914567890', 'traveler', '2024-01-26 12:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `bus_stations`
--

CREATE TABLE `bus_stations` (
  `id` int(11) NOT NULL,
  `station_name` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `contact_phone` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bus_stations`
--

INSERT INTO `bus_stations` (`id`, `station_name`, `city`, `address`, `contact_phone`) VALUES
(1, 'Meskel Square Bus Terminal', 'Addis Ababa', 'Meskel Square, Addis Ababa', '+251111111111'),
(2, 'Bahir Dar Central Station', 'Bahir Dar', 'Near Tana Hotel, Bahir Dar', '+251222222222'),
(3, 'Mekelle Main Station', 'Mekelle', 'Downtown Mekelle', '+251333333333'),
(4, 'Hawassa Bus Terminal', 'Hawassa', 'Lake Hawassa Road', '+251444444444'),
(5, 'Jimma Central Station', 'Jimma', 'Jimma Town Center', '+251555555555'),
(6, 'Dire Dawa Station', 'Dire Dawa', 'Dire Dawa Downtown', '+251666666666'),
(7, 'Gondar Bus Terminal', 'Gondar', 'Near Fasil Castle', '+251777777777'),
(8, 'Harar Station', 'Harar', 'Harar Jugol', '+251888888888'),
(9, 'Arba Minch Bus Station', 'Arba Minch', 'Arba Minch City Center', '+251999999999'),
(10, 'Wolkite Bus Station', 'Wolkite', 'Wolkite Main Road', '+251113333333'),
(11, 'Halaba Bus Station', 'Halaba', 'Halaba Kulito', '+251446666666'),
(12, 'Hossana Bus Terminal', 'Hossana', 'Hossana City Center', '+251465555555'),
(13, 'Adama Bus Station', 'Adama', 'Adama Express Terminal', '+251221111111'),
(14, 'Desse Bus Terminal', 'Desse', 'Desse Piassa', '+251331111111'),
(15, 'Kombolcha Station', 'Kombolcha', 'Kombolcha Industrial Park', '+251332222222'),
(16, 'Debre Markos Station', 'Debre Markos', 'Debre Markos Center', '+251581111111'),
(17, 'Nekemte Bus Station', 'Nekemte', 'Nekemte Main Road', '+251571111111'),
(18, 'Shashemene Terminal', 'Shashemene', 'Shashemene Junction', '+251461111111'),
(19, 'Bishoftu Bus Station', 'Bishoftu', 'Bishoftu Lake Side', '+251114444444');

-- --------------------------------------------------------

--
-- Table structure for table `buses`
--

CREATE TABLE `buses` (
  `id` int(11) NOT NULL,
  `bus_number` varchar(20) NOT NULL,
  `bus_name` varchar(100) NOT NULL,
  `total_seats` int(11) DEFAULT 40,
  `amenities` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buses`
--

INSERT INTO `buses` (`id`, `bus_number`, `bus_name`, `total_seats`, `amenities`, `image_url`) VALUES
(1, 'FB-001', 'Fiker Luxury Express', 40, 'WiFi, AC, Reclining Seats, TV, Toilet', 'bus1.png'),
(2, 'FB-002', 'Fiker Sleeper', 30, 'WiFi, AC, Sleeper Beds, Blankets', 'bus2.png'),
(3, 'FB-003', 'Fiker Executive', 35, 'WiFi, AC, Extra Legroom, Snacks', 'bus3.png'),
(4, 'FB-004', 'Fiker Standard', 45, 'AC, Comfortable Seats', 'bus4.png'),
(5, 'FB-005', 'Fiker Premium', 32, 'WiFi, AC, Massage Seats, Meal Service', 'bus5.png');

-- --------------------------------------------------------

--
-- Table structure for table `bus_schedules`
--

CREATE TABLE `bus_schedules` (
  `id` int(11) NOT NULL,
  `bus_id` int(11) NOT NULL,
  `departure_station_id` int(11) NOT NULL,
  `arrival_station_id` int(11) NOT NULL,
  `departure_time` datetime NOT NULL,
  `arrival_time` datetime NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `available_seats` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bus_schedules`
--

INSERT INTO `bus_schedules` (`id`, `bus_id`, `departure_station_id`, `arrival_station_id`, `departure_time`, `arrival_time`, `price`, `available_seats`) VALUES
(1, 1, 1, 2, '2024-01-27 08:00:00', '2024-01-27 14:00:00', 25.00, 35),
(2, 2, 1, 3, '2024-01-27 10:00:00', '2024-01-27 18:00:00', 30.00, 20),
(3, 3, 1, 4, '2024-01-27 07:00:00', '2024-01-27 12:00:00', 20.00, 25),
(4, 4, 1, 5, '2024-01-27 09:00:00', '2024-01-27 15:00:00', 18.00, 40),
(5, 5, 2, 1, '2024-01-27 16:00:00', '2024-01-27 22:00:00', 25.00, 30),
(6, 1, 1, 6, '2024-01-28 06:00:00', '2024-01-28 14:00:00', 35.00, 35),
(7, 2, 1, 7, '2024-01-28 08:00:00', '2024-01-28 16:00:00', 28.00, 20),
(8, 3, 1, 8, '2024-01-28 07:00:00', '2024-01-28 15:00:00', 32.00, 25),
(9, 4, 2, 3, '2024-01-28 09:00:00', '2024-01-28 14:00:00', 22.00, 40),
(10, 5, 3, 1, '2024-01-28 17:00:00', '2024-01-29 01:00:00', 30.00, 30);

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `booking_number` varchar(20) NOT NULL,
  `user_id` int(11) NOT NULL,
  `schedule_id` int(11) NOT NULL,
  `seat_numbers` varchar(100) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `booking_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','confirmed','cancelled') DEFAULT 'pending',
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `booking_number`, `user_id`, `schedule_id`, `seat_numbers`, `total_amount`, `booking_date`, `status`, `payment_status`) VALUES
(1, 'FB20240126001', 2, 1, '1,2,3', 75.00, '2024-01-26 12:00:00', 'confirmed', 'paid'),
(2, 'FB20240126002', 3, 2, '5,6', 60.00, '2024-01-26 12:30:00', 'confirmed', 'paid'),
(3, 'FB20240126003', 4, 3, '10', 20.00, '2024-01-26 13:00:00', 'pending', 'pending'),
(4, 'FB20240126004', 2, 4, '15,16', 36.00, '2024-01-26 13:30:00', 'confirmed', 'paid'),
(5, 'FB20240126005', 3, 1, '20', 25.00, '2024-01-26 14:00:00', 'cancelled', 'failed');

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('unread','read','replied') DEFAULT 'unread'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `contact_messages`
--

INSERT INTO `contact_messages` (`id`, `name`, `email`, `subject`, `message`, `submitted_at`, `status`) VALUES
(1, 'Test User', 'test@example.com', 'Test Message', 'This is a test message to check the contact form functionality.', '2024-01-26 12:00:00', 'read'),
(2, 'Another User', 'user@example.com', 'Bus Schedule Inquiry', 'I would like to know about the bus schedules from Addis to Bahir Dar for next week.', '2024-01-26 12:30:00', 'unread');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `bus_stations`
--
ALTER TABLE `bus_stations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `buses`
--
ALTER TABLE `buses`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `bus_number` (`bus_number`);

--
-- Indexes for table `bus_schedules`
--
ALTER TABLE `bus_schedules`
  ADD PRIMARY KEY (`id`),
  ADD KEY `bus_id` (`bus_id`),
  ADD KEY `departure_station_id` (`departure_station_id`),
  ADD KEY `arrival_station_id` (`arrival_station_id`);

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `booking_number` (`booking_number`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `schedule_id` (`schedule_id`);

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `bus_stations`
--
ALTER TABLE `bus_stations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `buses`
--
ALTER TABLE `buses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `bus_schedules`
--
ALTER TABLE `bus_schedules`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bus_schedules`
--
ALTER TABLE `bus_schedules`
  ADD CONSTRAINT `bus_schedules_ibfk_1` FOREIGN KEY (`bus_id`) REFERENCES `buses` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bus_schedules_ibfk_2` FOREIGN KEY (`departure_station_id`) REFERENCES `bus_stations` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bus_schedules_ibfk_3` FOREIGN KEY (`arrival_station_id`) REFERENCES `bus_stations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`schedule_id`) REFERENCES `bus_schedules` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;