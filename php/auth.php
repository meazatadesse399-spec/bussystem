<?php
session_start();
include 'config.php';

$action = isset($_GET['action']) ? $_GET['action'] : 'login';

if ($action == 'logout') {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';
if (!$redirect && isset($_POST['redirect'])) {
    $redirect = $_POST['redirect'];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($action == 'signup') {
        // Handle signup
        $username = $_POST['username'];
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $full_name = $_POST['full_name'];
        $phone = $_POST['phone'];
        
        // Check if user exists
        $check_sql = "SELECT id FROM users WHERE email = ? OR username = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists!";
        } else {
            // Insert new user
            $sql = "INSERT INTO users (username, email, password, full_name, phone) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssss", $username, $email, $password, $full_name, $phone);
            
            if ($stmt->execute()) {
                $_SESSION['user_id'] = $stmt->insert_id;
                $_SESSION['username'] = $username;
                $_SESSION['user_type'] = 'traveler';
                
                if ($redirect) {
                    header("Location: " . $redirect);
                } else {
                    header("Location: ../dashboard/traveler.php");
                }
                exit();
            } else {
                $error = "Error creating account. Please try again.";
            }
        }
    } elseif ($action == 'login') {
        // Handle login
        $email = $_POST['email'];
        $password = $_POST['password'];
        
        $sql = "SELECT id, username, password, user_type FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                
                if ($redirect) {
                    header("Location: " . $redirect);
                } elseif ($user['user_type'] == 'admin') {
                    header("Location: ../dashboard/admin.php");
                } else {
                    header("Location: ../dashboard/traveler.php");
                }
                exit();
            } else {
                $error = "Invalid password!";
            }
        } else {
            $error = "User not found!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ucfirst($action); ?> - Fiker Bus</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('../assets/images/bus-hero.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            padding: 20px;
        }
        
        .auth-box {
            background-color: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-header h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .auth-header p {
            color: #666;
        }
        
        .auth-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
        }
        
        .form-group label {
            margin-bottom: 5px;
            color: #555;
            font-weight: 500;
        }
        
        .form-group input {
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .auth-btn {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 5px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .auth-btn:hover {
            background-color: #2980b9;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .auth-footer a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        
        .auth-footer a:hover {
            text-decoration: underline;
        }
        
        .error-message {
            background-color: #ffebee;
            color: #c62828;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-box">
            <div class="auth-header">
                <h2><?php echo ucfirst($action); ?> to Fiker</h2>
                <p><?php echo $action == 'login' ? 'Welcome back! Please login to continue.' : 'Create an account to book tickets'; ?></p>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <?php if ($redirect): ?>
                    <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
                <?php endif; ?>
                <?php if ($action == 'signup'): ?>
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="tel" id="phone" name="phone" required>
                    </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <?php if ($action == 'signup'): ?>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                <?php endif; ?>
                
                <button type="submit" class="auth-btn">
                    <?php echo $action == 'login' ? 'Login' : 'Sign Up'; ?>
                </button>
            </form>
            
            <div class="auth-footer">
                <?php if ($action == 'login'): ?>
                    <p>Don't have an account? <a href="?action=signup">Sign up here</a></p>
                <?php else: ?>
                    <p>Already have an account? <a href="?action=login">Login here</a></p>
                <?php endif; ?>
                
                <p><a href="../index.php">← Back to Home</a></p>
            </div>
        </div>
    </div>
    
    <script>
        // Password confirmation validation
        <?php if ($action == 'signup'): ?>
        document.querySelector('form').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>