<?php
include 'config.php';

$password = 'password'; // The password you want to set
$username = 'admin';

// Hash the password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Update the user
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
$stmt->bind_param("ss", $hashed_password, $username);

if ($stmt->execute()) {
    echo "Password for user '$username' has been successfully reset to '$password'.<br>";
    echo "Hash generated: " . $hashed_password . "<br>";
    echo "<a href='auth.php?action=login'>Go to Login</a>";
} else {
    echo "Error updating password: " . $conn->error;
}
?>
