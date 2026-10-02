<?php 
include 'db_config.php'; 
session_start();

// 1. መግባታቸውን ማረጋገጥ (Secure Login Check)
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$admin_id = $_SESSION['user_id'];

// Get fresh admin data
$adminQuery = $conn->query("SELECT * FROM users WHERE id = '$admin_id' LIMIT 1");
$admin = $adminQuery->fetch_assoc();

if (!$admin) { 
    session_destroy(); 
    header("Location: login.php"); 
    exit(); 
}

// 2. ፕሮፋይል ማደሻ (Proper Profile Update Logic with Prepared Statements)
if (isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $pic = $admin['profile_pic'];

    // Handle Image Upload
    if (!empty($_FILES['image']['name'])) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        
        $pic = time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $target_dir . $pic);
    }

    if (!empty($password)) {
        // Update with New Password (plain text for now as per your original code)
        $stmt = $conn->prepare("UPDATE users SET fullname=?, username=?, password=?, profile_pic=? WHERE id=?");
        $stmt->bind_param("ssssi", $fullname, $username, $password, $pic, $admin_id);
    } else {
        // Update without changing password
        $stmt = $conn->prepare("UPDATE users SET fullname=?, username=?, profile_pic=? WHERE id=?");
        $stmt->bind_param("sssi", $fullname, $username, $pic, $admin_id);
    }

    if ($stmt->execute()) {
        header("Location: admin.php?msg=Profile Updated Successfully");
        exit();
    } else {
        header("Location: admin.php?msg=Error Updating Profile");
        exit();
    }
}

// 3. ዳታዎችን በደህንነት መሳብ (Safe Fetching preserved)
function safe_fetch($conn, $sql) {
    $result = $conn->query($sql);
    if ($result) {
        return $result->fetch_all(MYSQLI_ASSOC);
    } else {
        return [];
    }
}

$employees = safe_fetch($conn, "SELECT * FROM users WHERE role IN ('Driver', 'Staff')");
$buses = safe_fetch($conn, "SELECT * FROM buses");
$routes = safe_fetch($conn, "SELECT * FROM routes");
$reports = safe_fetch($conn, "SELECT * FROM reports ORDER BY id DESC LIMIT 50");

$assignments_sql = "SELECT a.*, b.plate_number, r.start_point, r.end_point 
                    FROM assignments a 
                    LEFT JOIN buses b ON a.bus_id = b.id 
                    LEFT JOIN routes r ON a.route_id = r.id 
                    ORDER BY a.id DESC LIMIT 20";
$assignments = safe_fetch($conn, $assignments_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>OBSCS Admin | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --main: #2c3e50; --blue: #3498db; --green: #27ae60; --red: #e74c3c; --bg: #f1f2f6; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; background: var(--bg); }
        .sidebar { width: 260px; background: var(--main); height: 100vh; color: #fff; position: fixed; }
        .sidebar-brand { padding: 30px 20px; text-align: center; border-bottom: 1px solid #3e5871; }
        .sidebar-brand img { width: 80px; height: 80px; border-radius: 50%; border: 3px solid var(--blue); object-fit: cover;}
        .nav-link { display: block; padding: 15px 25px; color: #dcdde1; text-decoration: none; transition: 0.3s; cursor: pointer; }
        .nav-link:hover, .nav-link.active { background: #34495e; color: #fff; border-left: 5px solid var(--blue); }
        .content { margin-left: 260px; width: 100%; padding: 30px; }
        .card { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .section { display: none; }
        .section.active { display: block; animation: fadeIn 0.4s; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { padding: 12px; border-bottom: 1px solid #eee; text-align: left; }
        th { background: #fafafa; color: #666; font-size: 13px; }
        .form-row { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        input, select { padding: 10px; border: 1px solid #ddd; border-radius: 5px; min-width: 150px; flex: 1; }
        .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; color: #fff; font-weight: bold; }
        .btn-green { background: var(--green); }
        .btn-blue { background: var(--blue); }
        .btn-red { background: var(--red); }
        #msg { position: fixed; top: 20px; right: 20px; background: var(--green); color: #fff; padding: 15px 25px; border-radius: 5px; display: none; z-index: 100; box-shadow: 0 5px 10px rgba(0,0,0,0.2); }
    </style>
</head>
<body>

<div id="msg"></div>

<div class="sidebar">
    <div class="sidebar-brand">
        <!-- Admin Profile Picture in Sidebar -->
        <img src="uploads/<?php echo $admin['profile_pic'] ?: 'default.png'; ?>" onerror="this.src='https://via.placeholder.com/80'">
        <h4><?php echo htmlspecialchars($admin['fullname']); ?></h4>
        <small>System Administrator</small>
    </div>
    <a class="nav-link active" onclick="switchTab('users', this)"><i class="fa fa-users"></i> Employees</a>
    <a class="nav-link" onclick="switchTab('buses', this)"><i class="fa fa-bus"></i> Bus Fleet</a>
    <a class="nav-link" onclick="switchTab('routes', this)"><i class="fa fa-road"></i> Route Control</a>
    <a class="nav-link" onclick="switchTab('trips', this)"><i class="fa fa-calendar-check"></i> Trip Assignments</a>
    <a class="nav-link" onclick="switchTab('reports', this)"><i class="fa fa-exclamation-triangle"></i> Reports</a>
    <a class="nav-link" onclick="switchTab('profile', this)"><i class="fa fa-user-cog"></i> My Profile</a>
    <a class="nav-link" href="logout.php" style="color:var(--red); margin-top:30px;"><i class="fa fa-sign-out-alt"></i> Logout</a>
</div>

<div class="content">

    <!-- 1. Employee Section (Preserved) -->
    <div id="users" class="section active">
        <h2><i class="fa fa-users"></i> Employee Management</h2>
        <div class="card">
            <form action="admin_process.php" method="POST" class="form-row">
                <input type="text" name="empName" placeholder="Full Name" required>
                <input type="text" name="empUser" placeholder="Username" required>
                <input type="password" name="empPass" placeholder="Password" required>
                <select name="empRole">
                    <option value="Driver">Driver</option>
                    <option value="Staff">Station Staff</option>
                </select>
                <button type="submit" name="add_employee" class="btn btn-green">Register</button>
            </form>
            <table>
                <tr><th>Full Name</th><th>Username</th><th>Role</th><th>Action</th></tr>
                <?php foreach($employees as $e): ?>
                <tr>
                    <td><?php echo htmlspecialchars($e['fullname']); ?></td>
                    <td><?php echo htmlspecialchars($e['username']); ?></td>
                    <td><b><?php echo $e['role']; ?></b></td>
                    <td><a href="delete.php?type=user&id=<?php echo $e['id']; ?>" class="btn btn-red" onclick="return confirm('Delete?')">Delete</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- 2. Bus Section (Preserved) -->
    <div id="buses" class="section">
        <h2><i class="fa fa-bus"></i> Bus Fleet</h2>
        <div class="card">
            <form action="admin_process.php" method="POST" class="form-row">
                <input type="text" name="busPlate" placeholder="Plate Number" required>
                <input type="number" name="busCap" placeholder="Seats" required>
                <button type="submit" name="add_bus" class="btn btn-blue">Add Bus</button>
            </form>
            <table>
                <tr><th>Plate</th><th>Capacity</th><th>Action</th></tr>
                <?php foreach($buses as $b): ?>
                <tr>
                    <td><?php echo $b['plate_number']; ?></td>
                    <td><?php echo $b['capacity']; ?></td>
                    <td><a href="delete.php?type=bus&id=<?php echo $b['id']; ?>" class="btn btn-red">Delete</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- 3. Route Section (Preserved) -->
    <div id="routes" class="section">
        <h2><i class="fa fa-road"></i> Route Control</h2>
        <div class="card">
            <form action="admin_process.php" method="POST" class="form-row">
                <input type="text" name="rStart" placeholder="From" required>
                <input type="text" name="rEnd" placeholder="To" required>
                <button type="submit" name="add_route" class="btn btn-blue">Save Route</button>
            </form>
            <table>
                <tr><th>Start</th><th>End</th><th>Action</th></tr>
                <?php foreach($routes as $r): ?>
                <tr>
                    <td><?php echo $r['start_point']; ?></td>
                    <td><?php echo $r['end_point']; ?></td>
                    <td><a href="delete.php?type=route&id=<?php echo $r['id']; ?>" class="btn btn-red">Delete</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- Find the Trip Assignments Section and Replace it with this -->
<div id="trips" class="section">
    <h2><i class="fa fa-calendar-check"></i> Trip Assignments</h2>
    <div class="card">
        <!-- New Trip Form -->
        <form action="admin_process.php" method="POST" class="form-row">
            <select name="driver" required>
                <option value="">Select Driver</option>
                <?php foreach($employees as $e) if($e['role']=='Driver') echo "<option value='{$e['fullname']}'>{$e['fullname']}</option>"; ?>
            </select>
            <select name="bus_id" required>
                <option value="">Select Bus</option>
                <?php foreach($buses as $b) echo "<option value='{$b['id']}'>{$b['plate_number']}</option>"; ?>
            </select>
            <select name="route_id" required>
                <option value="">Select Route</option>
                <?php foreach($routes as $r) echo "<option value='{$r['id']}'>{$r['start_point']} - {$r['end_point']}</option>"; ?>
            </select>
            
            <!-- Added 'min' attribute to prevent past dates -->
            <input type="date" name="travel_date" min="<?php echo date('Y-m-d'); ?>" required>
            
            <!-- Admin should enter time like 02:30 PM -->
            <input type="text" name="time" placeholder="Time (e.g. 02:30 PM)" required>
            
            <input type="number" name="price" placeholder="Price (ETB)" required>
            <button type="submit" name="add_assignment" class="btn btn-green">Assign Trip</button>
        </form>

        <table>
            <tr><th>Date</th><th>Driver</th><th>Bus</th><th>Route</th><th>Price</th><th>Action</th></tr>
            <?php foreach($assignments as $a): ?>
            <tr>
                <td><?php echo $a['travel_date']; ?></td>
                <td><?php echo $a['driver_name']; ?></td>
                <td><?php echo $a['plate_number']; ?></td>
                <td><?php echo $a['start_point']." - ".$a['end_point']; ?></td>
                <td><?php echo $a['price']; ?> ETB</td>
                <td><a href="delete.php?type=assign&id=<?php echo $a['id']; ?>" class="btn btn-red" onclick="return confirm('Remove trip?')">Remove</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
    <!-- 5. Reports Section (Preserved) -->
    <div id="reports" class="section">
        <h2><i class="fa fa-exclamation-triangle"></i> Emergency Reports</h2>
        <div class="card">
            <table>
                <tr><th>Time</th><th>Sender</th><th>Type</th><th>Message</th><th>Action</th></tr>
                <?php foreach($reports as $rep): ?>
                <tr>
                    <td><?php echo date('M d, H:i', strtotime($rep['created_at'])); ?></td>
                    <td><?php echo htmlspecialchars($rep['sender']); ?></td>
                    <td><?php echo htmlspecialchars($rep['type']); ?></td>
                    <td><?php echo htmlspecialchars($rep['message']); ?></td>
                    <td><a href="delete.php?type=report&id=<?php echo $rep['id']; ?>" class="btn btn-green">Resolve</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- 6. Profile Section (PROPERLY IMPLEMENTED) -->
    <div id="profile" class="section">
        <h2><i class="fa fa-user-cog"></i> Profile Settings</h2>
        <div class="card">
            <form action="admin.php" method="POST" enctype="multipart/form-data">
                <div style="text-align:center; margin-bottom:20px;">
                    <!-- Current Profile Image Preview -->
                    <img id="preview" src="uploads/<?php echo $admin['profile_pic'] ?: 'default.png'; ?>" style="width:150px; height:150px; border-radius:50%; object-fit:cover; border: 4px solid #ddd;">
                    <br>
                    <label class="btn btn-blue" style="display:inline-block; margin-top:10px;">
                        <i class="fa fa-camera"></i> Change Photo
                        <input type="file" name="image" style="display:none;" onchange="document.getElementById('preview').src = window.URL.createObjectURL(this.files[0])">
                    </label>
                </div>
                
                <div class="form-row">
                    <div style="flex:1;">
                        <label>Full Name</label>
                        <input type="text" name="fullname" value="<?php echo htmlspecialchars($admin['fullname']); ?>" required style="width:100%; margin-top:5px;">
                    </div>
                    <div style="flex:1;">
                        <label>Username</label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($admin['username']); ?>" required style="width:100%; margin-top:5px;">
                    </div>
                </div>
                
                <div class="form-row">
                    <div style="flex:1;">
                        <label>New Password (Leave blank to keep current)</label>
                        <input type="password" name="password" placeholder="••••••••" style="width:100%; margin-top:5px;">
                    </div>
                </div>

                <button type="submit" name="update_profile" class="btn btn-green" style="width:100%; font-size:16px;">
                    <i class="fa fa-save"></i> Save Profile Changes
                </button>
            </form>
        </div>
    </div>

</div>

<script>
    function switchTab(id, el) {
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        document.getElementById(id).classList.add('active');
        el.classList.add('active');
        localStorage.setItem('lastTab', id);
    }

    window.onload = () => {
    // ... existing logic ...
    const urlParams = new URLSearchParams(window.location.search);
    if(urlParams.has('error')) {
        const err = urlParams.get('error');
        const msgBox = document.getElementById('msg');
        msgBox.style.background = "#e74c3c"; // Red for error
        
        if(err === 'past_time') msgBox.innerText = "❌ Error: Cannot assign trips in the past!";
        else if(err === 'driver_busy') msgBox.innerText = "❌ Error: Driver is already assigned at this time!";
        else if(err === 'bus_busy') msgBox.innerText = "❌ Error: Bus is already assigned at this time!";
        
        msgBox.style.display = 'block';
        setTimeout(() => { msgBox.style.display = 'none'; }, 4000);
    }
};
</script>

</body>
</html>