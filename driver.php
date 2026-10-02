<?php 
session_start(); 
include 'db_config.php'; 

// 1. AUTH CHECK - Redirect to login if session is invalid
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Driver') {
    header("Location: login.php");
    exit();
}

$driver_name = $_SESSION['fullname']; 

// --- DATABASE ENDPOINT: Handle Assignment Completion (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'complete_assignment') {
    $assignId = $_POST['assign_id'];
    
    // Deletes the assignment so the bus and driver are free for reassignment by Admin
    $stmt = $conn->prepare("DELETE FROM assignments WHERE id = ?");
    $stmt->bind_param("i", $assignId);
    if ($stmt->execute()) { echo "success"; } else { echo "error"; }
    exit;
}

// --- DATABASE ENDPOINT: Handle Saving Reports (POST) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'send_report') {
    $sender = mysqli_real_escape_string($conn, $_POST['sender']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    
    // Insert into the 'reports' table (Sender, Type, Message)
  // መስመር 31 አካባቢ ያለውን እንዲህ አስተካክለው
$stmt = $conn->prepare("INSERT INTO reports (sender, type, message) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $sender, $type, $message);
    
    if ($stmt->execute()) { 
        echo "success"; 
    } else { 
        echo "error: " . $conn->error; 
    }
    exit;
}

// 2. FETCH ASSIGNED SCHEDULE FOR CURRENT DRIVER ONLY (Privacy Filter)
$query = "SELECT a.*, r.start_point, r.end_point, b.plate_number 
          FROM assignments a 
          JOIN routes r ON a.route_id = r.id 
          JOIN buses b ON a.bus_id = b.id 
          WHERE a.driver_name = ? 
          ORDER BY a.travel_date ASC, a.departure_time ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $driver_name);
$stmt->execute();
$myTrips = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OBSCS - Driver Dashboard</title>

<script>
    // JS AUTH CHECK - Verify local storage session
    const user = JSON.parse(localStorage.getItem('currentUser'));
    if (!user || user.role !== 'Driver') {
        window.location.href = 'login.php';
    }
    const currentDriver = "<?php echo htmlspecialchars($driver_name); ?>";
</script>

<style>
    :root { --primary: #2c3e50; --accent: #3498db; --success: #2ecc71; --danger: #e74c3c; --warning: #f1c40f; }
    body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; background: #f4f7f6; }
    .sidebar { width: 250px; background: var(--primary); color: white; height: 100vh; padding: 20px; position: fixed; }
    .sidebar a { display: block; color: #bdc3c7; padding: 12px; text-decoration: none; cursor: pointer; border-radius: 5px; margin-bottom: 5px; transition: 0.3s; }
    .sidebar a:hover, .sidebar a.active { background: #34495e; color: white; }
    .main { margin-left: 270px; padding: 30px; width: calc(100% - 270px); }
    .section { display: none; animation: fadeIn 0.3s ease; }
    .active-section { display: block; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
    .btn { padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
    .btn-success { background: var(--success); color: white; }
    .btn-danger { background: var(--danger); color: white; }
    .btn-primary { background: var(--accent); color: white; }
    table { width: 100%; border-collapse: collapse; margin-top: 15px; }
    th, td { text-align: left; padding: 12px; border-bottom: 1px solid #ddd; }
    th { background: #f8f9fa; color: #333; }
    textarea, select { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 5px; font-family: inherit; }
    .profile-img-container { width: 120px; height: 120px; margin: 0 auto 15px; border: 3px solid var(--accent); border-radius: 50%; overflow: hidden; }
    .sidebar-thumb { width: 40px; height: 40px; border-radius: 50%; vertical-align: middle; margin-right: 10px; object-fit: cover; }
</style>
</head>
<body>

<div class="sidebar">
    <h2 style="color: var(--accent);">OBSCS Driver</h2>
    <div style="padding: 10px 0; display: flex; align-items: center;">
        <img src="https://via.placeholder.com/40" id="sideThumb" class="sidebar-thumb">
        <span><?php echo htmlspecialchars($driver_name); ?></span>
    </div>
    <hr style="border: 0; border-top: 1px solid #34495e; margin: 15px 0;">
    <a onclick="showSection('schedule')" id="nav-schedule" class="nav-link active">📅 My Schedule</a>
    <a onclick="showSection('status')" id="nav-status" class="nav-link">🚦 Trip Status</a>
    <a onclick="showSection('report')" id="nav-report" class="nav-link">⚠️ Report Problem</a>
    <a onclick="showSection('profile')" id="nav-profile" class="nav-link">👤 My Profile</a>
    <a href="logout.php" style="margin-top: 30px; color: #ff7675;">🚪 Logout</a>
</div>

<div class="main">

    <!-- 1. MY SCHEDULE -->
    <div id="schedule" class="section active-section">
        <h1>My Assigned Schedule</h1>
        <div id="scheduleList">
            <?php if($myTrips && $myTrips->num_rows > 0): ?>
                <?php while($row = $myTrips->fetch_assoc()): 
                    $fullRoute = $row['start_point'] . " - " . $row['end_point'];
                    $busPlate = $row['plate_number'];
                ?>
                    <div class="card">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <h3 style="margin:0; color:var(--primary);"><?php echo $fullRoute; ?></h3>
                                <p style="margin:5px 0 0 0;">📅 Date: <b><?php echo $row['travel_date']; ?></b> | 🕒 Time: <b><?php echo $row['departure_time']; ?></b> | 🚌 Bus: <b><?php echo $busPlate; ?></b></p>
                            </div>
                            <button class="btn btn-success" onclick="startTrip('<?php echo $row['id']; ?>', '<?php echo $fullRoute; ?>', '<?php echo $busPlate; ?>', '<?php echo $row['departure_time']; ?>')">
                                Start Trip
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="card"><p>No trips currently assigned to you, <b><?php echo htmlspecialchars($driver_name); ?></b>.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. TRIP STATUS -->
    <div id="status" class="section">
        <h1>Active Trip Status</h1>
        <div id="activeTripControl" class="card">
            <p>No trip currently started. Go to "My Schedule" to start a trip.</p>
        </div>
    </div>

    <!-- 3. REPORT PROBLEM -->
    <div id="report" class="section">
        <h1>Report Incident</h1>
        <div class="card">
            <p style="color: #666;">This alert will be sent directly to the Admin and saved in the database logs.</p>
            <label>Issue Type</label>
            <select id="issueType">
                <option value="Mechanical Breakdown">Mechanical Breakdown</option>
                <option value="Traffic Delay">Traffic Delay</option>
                <option value="Medical Emergency">Medical Emergency</option>
                <option value="Accident">Accident</option>
            </select>
            <textarea id="issueDesc" placeholder="Provide specific details about the problem..."></textarea>
            <button class="btn btn-danger" style="width:100%;" onclick="sendDriverReport()">Submit Alert to Admin</button>
        </div>
    </div>

    <!-- 4. MY PROFILE -->
    <div id="profile" class="section">
        <h1>My Profile</h1>
        <div class="card" style="text-align: center;">
            <div class="profile-img-container"><img id="mainProfilePic" src="https://via.placeholder.com/120"></div>
            <input type="file" id="fileInput" style="display:none;" onchange="previewAndSaveImage(event)">
            <button class="btn btn-primary" onclick="document.getElementById('fileInput').click()">📸 Upload Photo</button>
            <h2 style="margin-top:20px;"><?php echo htmlspecialchars($driver_name); ?></h2>
        </div>
    </div>

</div>

<script>
    let activeTrip = null;

    document.addEventListener('DOMContentLoaded', () => {
        setupProfile();
        checkActiveTrip();
    });

    function showSection(id) {
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active-section'));
        document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
        document.getElementById(id).classList.add('active-section');
        document.getElementById('nav-' + id).classList.add('active');
    }

    function startTrip(id, route, bus, time) {
        const tripData = { id, route, bus, time };
        localStorage.setItem('active_trip', JSON.stringify(tripData));
        activeTrip = tripData;
        alert("Trip Started! Drive safely.");
        checkActiveTrip();
        showSection('status');
    }

    function checkActiveTrip() {
        const savedTrip = JSON.parse(localStorage.getItem('active_trip'));
        const container = document.getElementById('activeTripControl');
        
        if (savedTrip) {
            activeTrip = savedTrip;
            container.innerHTML = `
                <div style="text-align:center;">
                    <h2 style="color:var(--success);">● Currently En Route</h2>
                    <div style="margin: 15px 0; font-size: 1.1rem;">
                        <p><strong>Route:</strong> ${savedTrip.route}</p>
                        <p><strong>Bus:</strong> ${savedTrip.bus}</p>
                    </div>
                    <button class="btn btn-danger" onclick="completeTrip()">Finish Trip / Arrived</button>
                </div>
            `;
        } else {
            container.innerHTML = `<p>No trip currently started. Go to "My Schedule" to start a trip.</p>`;
        }
    }

    function completeTrip() {
        if(!confirm("Confirm Arrival? This clears your schedule and notifies Admin.")) return;
        const savedTrip = JSON.parse(localStorage.getItem('active_trip'));

        if(savedTrip) {
            // 1. Send Automated Arrival Report to Database
            const reportData = new FormData();
            reportData.append('action', 'send_report');
            reportData.append('sender', currentDriver);
            reportData.append('type', 'Trip Completed');
            reportData.append('message', `ARRIVAL NOTIFICATION: Driver ${currentDriver} has arrived at destination. Route: ${savedTrip.route}. Bus: ${savedTrip.bus}.`);
            
            fetch(window.location.pathname, { method: 'POST', body: reportData })
            .then(() => {
                // 2. Clear assignment from database to allow Admin reassignment
                const completeData = new FormData();
                completeData.append('action', 'complete_assignment');
                completeData.append('assign_id', savedTrip.id);
                return fetch(window.location.pathname, { method: 'POST', body: completeData });
            })
            .then(res => res.text())
            .then(result => {
                if(result.trim() === "success") {
                    alert("Trip ended. Schedule cleared.");
                    localStorage.removeItem('active_trip');
                    location.reload(); 
                }
            });
        }
    }

    // --- MANUAL REPORT LOGIC ---
    function sendDriverReport() {
        const type = document.getElementById('issueType').value;
        const desc = document.getElementById('issueDesc').value;
        if(!desc.trim()) return alert("Please provide details about the problem.");

        const formData = new FormData();
        formData.append('action', 'send_report');
        formData.append('sender', currentDriver);
        formData.append('type', type);
        formData.append('message', desc);

        // Sending to the current file's PHP backend handler
        fetch(window.location.pathname, { 
            method: 'POST', 
            body: formData 
        })
        .then(res => res.text())
        .then(result => {
            if(result.trim() === "success") {
                alert("Incident report has been saved and sent to Admin dashboard.");
                document.getElementById('issueDesc').value = "";
            } else {
                alert("Error sending report: " + result);
            }
        })
        .catch(err => alert("Connection error. Try again."));
    }

    function setupProfile() {
        const savedImg = localStorage.getItem(`profile_img_${currentDriver}`);
        if (savedImg) {
            const mainPic = document.getElementById('mainProfilePic');
            const sideThumb = document.getElementById('sideThumb');
            if(mainPic) mainPic.src = savedImg;
            if(sideThumb) sideThumb.src = savedImg;
        }
    }

    function previewAndSaveImage(event) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const imgData = e.target.result;
            document.getElementById('mainProfilePic').src = imgData;
            document.getElementById('sideThumb').src = imgData;
            localStorage.setItem(`profile_img_${currentDriver}`, imgData);
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>
</body>
</html>