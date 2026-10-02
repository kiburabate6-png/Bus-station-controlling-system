<?php
include 'db_config.php';
session_start();
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = trim($_POST['user']);
    $pass = $_POST['pass'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $foundUser = $result->fetch_assoc();
        
        if (password_verify($pass, $foundUser['password']) || $pass === $foundUser['password']) {
            
            $_SESSION['user_id'] = $foundUser['id'];
            $_SESSION['role'] = $foundUser['role'];
            $_SESSION['fullname'] = $foundUser['fullname']; 

            $redirectUrl = "";
            if ($foundUser['role'] == 'Admin') $redirectUrl = "admin.php";
            elseif ($foundUser['role'] == 'Driver') $redirectUrl = "driver.php"; 
            elseif ($foundUser['role'] == 'Staff') $redirectUrl = "staff.php";
            else $redirectUrl = "passenger.php";

            echo "<script>
                localStorage.setItem('currentUser', JSON.stringify({name: '{$foundUser['fullname']}', role: '{$foundUser['role']}'}));
                window.location.href = '$redirectUrl';
            </script>";
            exit();

        } else {
            $error = "Invalid Password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>OBSCS - Login</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #2c3e50; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 40px; border-radius: 10px; width: 350px; box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
        h2 { text-align:center; color: #2c3e50; margin-top: 0; }
        input, button { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; box-sizing: border-box; }
        button { background: #e67e22; color: white; border: none; font-weight: bold; cursor: pointer; transition: 0.3s; }
        button:hover { background: #d35400; }
        .error-box { color: white; background: #e74c3c; padding: 10px; border-radius: 5px; text-align: center; margin-bottom: 10px; font-size: 0.9rem; }
        
        /* New Back Button Styling */
        .back-home {
            display: block;
            text-align: center;
            margin-top: 15px;
            color: #34495e;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: 0.3s;
        }
        .back-home:hover {
            color: #e67e22;
        }
    </style>
</head>
<body>
    <div class="card">
        <h2>Login</h2>
        <?php if ($error): 
            ?>
            <div class="error-box">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>
        <form method="POST">
            <input type="text" name="user" placeholder="Username" required>
            <input type="password" name="pass" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>   
        <a href="register.php" style="display:block; text-align:center; margin-top:10px; color:#7f8c8d; text-decoration:none; font-size: 0.9rem;">Don't have an account? Register here</a>
        <a href="index.php" class="back-home">← Back to Home Page</a>
    </div>
</body>
</html>