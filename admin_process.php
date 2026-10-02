<?php
include 'db_config.php';
session_start();

// Secure Check: Ensure only logged-in users can process requests
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// --- 1. HANDLE EMPLOYEE REGISTRATION ---
if (isset($_POST['add_employee'])) {
    $fullname = mysqli_real_escape_string($conn, $_POST['empName']);
    $username = mysqli_real_escape_string($conn, $_POST['empUser']);
    $password = $_POST['empPass']; 
    $role     = mysqli_real_escape_string($conn, $_POST['empRole']);

    $check = $conn->prepare("SELECT id FROM users WHERE username = ?");
    if (!$check) { die("Database Error (users table): " . $conn->error); }
    
    $check->bind_param("s", $username);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        header("Location: admin.php?error=username_taken");
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO users (fullname, username, password, role) VALUES (?, ?, ?, ?)");
    if (!$stmt) { die("Database Error (Insert User): " . $conn->error); }
    
    $stmt->bind_param("ssss", $fullname, $username, $password, $role);
    $stmt->execute();
    header("Location: admin.php?msg=Employee Registered");
    exit();
}

// --- 2. HANDLE BUS ADDITION ---
if (isset($_POST['add_bus'])) {
    $plate = mysqli_real_escape_string($conn, $_POST['busPlate']);
    $cap   = mysqli_real_escape_string($conn, $_POST['busCap']);
    
    $stmt = $conn->prepare("INSERT INTO buses (plate_number, capacity) VALUES (?, ?)");
    if (!$stmt) { die("Database Error (buses table): " . $conn->error); }
    
    $stmt->bind_param("si", $plate, $cap);
    $stmt->execute();
    header("Location: admin.php?msg=Bus Added");
    exit();
}

// --- 3. HANDLE ROUTE CREATION ---
if (isset($_POST['add_route'])) {
    $start = mysqli_real_escape_string($conn, $_POST['rStart']);
    $end   = mysqli_real_escape_string($conn, $_POST['rEnd']);

    $stmt = $conn->prepare("INSERT INTO routes (start_point, end_point) VALUES (?, ?)");
    if (!$stmt) { die("Database Error (routes table): " . $conn->error); }
    
    $stmt->bind_param("ss", $start, $end);
    $stmt->execute();
    header("Location: admin.php?msg=Route Created");
    exit();
}

// --- 4. HANDLE TRIP ASSIGNMENTS (With Past Date & Conflict Checks) ---
if (isset($_POST['add_assignment'])) {
    $driver      = mysqli_real_escape_string($conn, $_POST['driver']);
    $bus_id      = mysqli_real_escape_string($conn, $_POST['bus_id']);
    $route_id    = mysqli_real_escape_string($conn, $_POST['route_id']);
    $travel_date = mysqli_real_escape_string($conn, $_POST['travel_date']);
    $time        = mysqli_real_escape_string($conn, $_POST['time']); // Expected format: 02:30 PM or 14:30
    $price       = mysqli_real_escape_string($conn, $_POST['price']);

    // --- STEP A: Prevent Past Date/Time ---
    // Sets timezone to Ethiopia/East Africa if not set in php.ini
    date_default_timezone_set('Africa/Addis_Ababa'); 
    
    $current_datetime = strtotime(date('Y-m-d H:i'));
    $input_datetime = strtotime($travel_date . ' ' . $time);

    if ($input_datetime < $current_datetime) {
        header("Location: admin.php?error=past_time");
        exit();
    }

    // --- STEP B: Check Driver Conflict (Already assigned at same time) ---
    $checkD = $conn->prepare("SELECT id FROM assignments WHERE driver_name = ? AND travel_date = ? AND departure_time = ?");
    if (!$checkD) { die("Database Error: 'assignments' table missing. " . $conn->error); }
    
    $checkD->bind_param("sss", $driver, $travel_date, $time);
    $checkD->execute();
    if ($checkD->get_result()->num_rows > 0) {
        header("Location: admin.php?error=driver_busy");
        exit();
    }

    // --- STEP C: Check Bus Conflict ---
    $checkB = $conn->prepare("SELECT id FROM assignments WHERE bus_id = ? AND travel_date = ? AND departure_time = ?");
    if (!$checkB) { die("Database Error: Bus check failed. " . $conn->error); }
    
    $checkB->bind_param("iss", $bus_id, $travel_date, $time);
    $checkB->execute();
    if ($checkB->get_result()->num_rows > 0) {
        header("Location: admin.php?error=bus_busy");
        exit();
    }

    // --- STEP D: Insert Assignment ---
    $stmt = $conn->prepare("INSERT INTO assignments (driver_name, bus_id, route_id, travel_date, departure_time, price) VALUES (?, ?, ?, ?, ?, ?)");
    if (!$stmt) { die("Database Error: Insert failed. " . $conn->error); }
    
    $stmt->bind_param("siissi", $driver, $bus_id, $route_id, $travel_date, $time, $price);

    if ($stmt->execute()) {
        header("Location: admin.php?msg=Trip Assigned Successfully");
    } else {
        header("Location: admin.php?error=db_error");
    }
    exit();
}

// Redirect if accessed directly
header("Location: admin.php");
exit();
?>