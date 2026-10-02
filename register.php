<?php
include 'db_config.php';
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name  = trim($_POST['regName']);
    $email = trim($_POST['regEmail']);
    $user  = trim($_POST['regUser']);
    $pass  = $_POST['regPass']; 
    
    // Automatically set role to Passenger
    $role  = "Passenger"; 

    // --- START VALIDATIONS ---
    if (empty($name) || empty($email) || empty($user) || empty($pass)) {
        $message = "Error: All fields are required!";
    }
    elseif (preg_match('~[0-9]~', $name)) {
        $message = "Error: Full Name cannot contain numbers.";
    } 
    elseif (strpos($email, '@') === false || strpos($email, '.') === false) {
        $message = "Error: Invalid email! Email must contain '@' and '.'";
    }
    elseif (strpos($user, ' ') !== false) {
        $message = "Error: Username cannot contain spaces.";
    } 
    elseif (strlen($pass) < 6) {
        $message = "Error: Password must be at least 6 characters long.";
    }
    else {
        // Check for duplicate username
        $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check->bind_param("s", $user);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            $message = "Error: Username already exists!";
        } else {
            // Hashing password
            $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (fullname, email, username, password, role) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $user, $hashed_pass, $role);
            
            if ($stmt->execute()) {
                echo "<script>alert('Registration Successful as Passenger!'); window.location.href='login.php';</script>";
            } else {
                $message = "Error: Something went wrong. Please try again.";
            }
            $stmt->close();
        }
        $check->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OBSCS - Register</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #2c3e50; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 10px; width: 400px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); }
        h2 { text-align: center; color: #2c3e50; margin-top: 0; margin-bottom: 5px; }
        p { text-align: center; color: #7f8c8d; font-size: 0.9rem; margin-bottom: 20px; }
        input, button { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; outline: none; }
        button { background: #e67e22; color: white; border: none; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 20px; }
        button:hover { background: #d35400; }
        .msg { color: #e74c3c; text-align: center; font-weight: bold; font-size: 0.9rem; margin-bottom: 10px; }
        .login-link { display: block; text-align: center; margin-top: 15px; color: #7f8c8d; text-decoration: none; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Passenger Sign Up</h2>
        <p>Create an account to book your trips</p>
        
        <?php if ($message != ""): ?>
            <div class="msg"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <input type="text" name="regName" placeholder="Full Name" value="<?php echo isset($_POST['regName']) ? htmlspecialchars($_POST['regName']) : ''; ?>" required>
            <input type="email" name="regEmail" placeholder="Email Address" value="<?php echo isset($_POST['regEmail']) ? htmlspecialchars($_POST['regEmail']) : ''; ?>" required>
            <input type="text" name="regUser" placeholder="Username" value="<?php echo isset($_POST['regUser']) ? htmlspecialchars($_POST['regUser']) : ''; ?>" required>
            <input type="password" name="regPass" placeholder="Password (Min. 6 chars)" required>
            
            <!-- Note: No dropdown here. Role is set to Passenger automatically in PHP -->
            
            <button type="submit">Create Passenger Account</button>
            <a href="login.php" class="login-link">Already have an account? Login here</a>
        </form>
    </div>
</body>
</html>