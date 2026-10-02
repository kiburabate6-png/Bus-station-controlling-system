<?php 
include 'db_config.php'; 
session_start();

// --- PHP BACKEND LOGIC ---

// 1. Handle Ticket Verification
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'verify_ticket') {
    $tId = $_POST['ticketId'];
    $stmt = $conn->prepare("UPDATE bookings SET status = 'Verified & Confirmed' WHERE ticketId = ?");
    $stmt->bind_param("s", $tId);
    if ($stmt->execute()) { echo "success"; } else { echo "error"; }
    exit;
}

// 2. Handle Counter Booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'counter_booking') {
    $tId = $_POST['ticketId'];
    $pName = $_POST['passenger'];
    $route = $_POST['route'];
    $seat = $_POST['seat'];
    $status = "Verified & Confirmed";
    $date = date("Y-m-d");

    $stmt = $conn->prepare("INSERT INTO bookings (ticketId, tripId, passengerName, route, seatNumber, bookingDate, status) VALUES (?, 0, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssisss", $tId, $pName, $route, $seat, $date, $status);
    
    if ($stmt->execute()) { echo "success"; } else { echo "error"; }
    exit;
}

// 3. Handle Sending General Reports & Clear Verified Log (Status update logic)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'send_report') {
    $sender = mysqli_real_escape_string($conn, $_SESSION['name'] ?? 'Staff Member');
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $msg = mysqli_real_escape_string($conn, $_POST['message']);
    
    // Insert into reports table
    $sql = "INSERT INTO reports (sender, type, message) VALUES ('$sender', '$type', '$msg')";
    
    if ($conn->query($sql)) { 
        // If report contains ticket IDs, update their status so they "disappear" from verified log
        if (isset($_POST['ticketIds']) && !empty($_POST['ticketIds'])) {
            $ids = explode(',', $_POST['ticketIds']);
            foreach ($ids as $id) {
                $cleanId = mysqli_real_escape_string($conn, $id);
                $conn->query("UPDATE bookings SET status = 'Verified & Reported' WHERE ticketId = '$cleanId'");
            }
        }
        echo "success"; 
    } else { 
        echo "error"; 
    }
    exit;
}

// --- DATA FETCHING ---
// Fetch tickets that are NOT yet reported
$tickets = $conn->query("SELECT * FROM bookings WHERE status = 'Pending Verification' ORDER BY id DESC");
$verifiedTickets = $conn->query("SELECT * FROM bookings WHERE status = 'Verified & Confirmed' ORDER BY id DESC LIMIT 50");
$assignResult = $conn->query("SELECT DISTINCT CONCAT(r.start_point, ' - ', r.end_point) as route FROM assignments a JOIN routes r ON a.route_id = r.id");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OBSCS - Staff Portal</title>
    <script>
        const user = JSON.parse(localStorage.getItem('currentUser'));
        if (!user || user.role !== 'Staff') {
            alert("Access Denied!");
            window.location.href = 'login.php';
        }
        function logout() {
            localStorage.removeItem('currentUser');
            window.location.href = 'login.php';
        }

        function showSection(sectionId) {
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(sec => sec.style.display = 'none');
            const target = document.getElementById(sectionId);
            if(target) target.style.display = 'block';
            
            const links = document.querySelectorAll('.sidebar a');
            links.forEach(link => link.classList.remove('active-nav'));
            if(event && event.currentTarget) event.currentTarget.classList.add('active-nav');
        }
    </script>
    <style>
        :root { --staff-green: #27ae60; --staff-dark: #1e8449; --bg-light: #f4f7f6; --warning: #f39c12; --blue: #3498db; --danger: #e74c3c;}
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; background-color: var(--bg-light); }
        .sidebar { width: 260px; height: 100vh; background: var(--staff-green); color: white; padding: 20px; position: fixed; box-sizing: border-box; z-index: 100;}
        .sidebar h2 { margin-top: 0; padding-bottom: 20px; border-bottom: 1px solid rgba(255,255,255,0.2); text-align: center; }
        .sidebar a { display: block; color: white; text-decoration: none; padding: 15px 10px; border-radius: 6px; cursor: pointer; transition: 0.3s; margin-bottom: 5px; }
        .sidebar a:hover, .active-nav { background: var(--staff-dark); padding-left: 15px; }
        .main { margin-left: 280px; padding: 40px; width: calc(100% - 280px); box-sizing: border-box; }
        .content-section { display: none; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 30px; border-top: 5px solid var(--staff-green); }
        input, select, textarea { padding: 12px; border: 1px solid #ddd; border-radius: 6px; width: 100%; box-sizing: border-box; margin-bottom: 10px; font-family: inherit;}
        .btn { padding: 8px 15px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: white; transition: 0.2s; font-size: 0.85rem;}
        .btn-blue { background: var(--blue); }
        .btn-success { background: var(--staff-green); }
        .btn-warning { background: var(--warning); }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; background: #fff; }
        th, td { text-align: left; padding: 15px; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; color: #666; font-size: 0.85rem; text-transform: uppercase; }
        .bg-verified { background: #d4edda; color: #155724; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 0.7rem; }
        .bulk-actions { margin-bottom: 15px; display: flex; align-items: center; gap: 10px; }
    </style>
</head>
<body>

<div class="sidebar">
    <h2>OBSCS Staff</h2>
    <a onclick="showSection('verify-section')" class="active-nav">✔️ Verify Tickets</a>
    <a onclick="showSection('verified-log-section')">📜 Verified Log</a>
    <a onclick="showSection('status-section')">📍 Update Bus Status</a>
    <a onclick="showSection('report-section')">📋 Send General Report</a>
    <div style="position: absolute; bottom: 20px; width: 85%;">
        <a onclick="logout()" style="color: #ffcccc; border-top: 1px solid rgba(255,255,255,0.2);">🚪 Logout</a>
    </div>
</div>

<div class="main">
    <!-- Removed "Station Control: Staff Member" line as requested -->

    <!-- SECTION 1: PENDING VERIFICATION -->
    <div id="verify-section" class="content-section" style="display: block;">
        <div class="card">
            <h3>📋 Incoming Tickets for Verification</h3>
            <table>
                <thead>
                    <tr><th>Ticket ID</th><th>Passenger</th><th>Route / Seat</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php if($tickets->num_rows > 0): ?>
                        <?php while($t = $tickets->fetch_assoc()): ?>
                        <tr>
                            <td><b><?php echo $t['ticketId']; ?></b></td>
                            <td><?php echo $t['passengerName']; ?></td>
                            <td><?php echo $t['route']; ?> (Seat: <?php echo $t['seatNumber']; ?>)</td>
                            <td><button class="btn btn-blue" onclick="approveTicket('<?php echo $t['ticketId']; ?>')">Verify & Approve</button></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px; color:#999;">No pending tickets.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECTION 2: VERIFIED LOG -->
    <div id="verified-log-section" class="content-section">
        <div class="card" style="border-top: 5px solid var(--blue);">
            <h3>📜 Verified & Confirmed Log</h3>
            <div class="bulk-actions">
                <button class="btn btn-warning" onclick="sendBulkReport()">🚀 Send Selected as Bulk Report</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 40px;"><input type="checkbox" id="selectAll" onclick="toggleSelectAll()"></th>
                        <th>Ticket ID</th>
                        <th>Passenger</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if($verifiedTickets->num_rows > 0): ?>
                        <?php while($v = $verifiedTickets->fetch_assoc()): ?>
                        <tr>
                            <td><input type="checkbox" class="ticket-checkbox" value="<?php echo $v['ticketId']; ?>" data-name="<?php echo $v['passengerName']; ?>"></td>
                            <td><b><?php echo $v['ticketId']; ?></b></td>
                            <td><?php echo $v['passengerName']; ?></td>
                            <td><span class="bg-verified">VERIFIED</span></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center; padding:20px; color:#999;">No verified tickets yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SECTION 4: GENERAL REPORT -->
    <div id="report-section" class="content-section">
        <div class="card" style="border-top: 5px solid var(--warning);">
            <h3>🚨 Send Emergency / General Report</h3>
            <select id="reportType">
                <option value="General Issue">General Issue</option>
                <option value="Bus Breakdown">Bus Breakdown</option>
                <option value="Delay">Weather/Road Delay</option>
                <option value="Accident">Accident</option>
            </select>
            <textarea id="reportMessage" placeholder="Describe the incident..." style="height: 120px;"></textarea>
            <button class="btn btn-warning" style="width: 100%;" onclick="sendGeneralReport()">Submit to Admin</button>
        </div>
    </div>

    <!-- SECTION 5: BUS STATUS -->
    <div id="status-section" class="content-section">
        <div class="card">
            <h3>🚌 Live Bus Management</h3>
            <div id="busStatusList"></div>
        </div>
    </div>
</div>

<script>
    const targetURL = window.location.href;

    function approveTicket(tId) {
        const formData = new FormData();
        formData.append('action', 'verify_ticket');
        formData.append('ticketId', tId);
        fetch(targetURL, { method: 'POST', body: formData })
        .then(res => res.text()).then(data => { if(data.trim() === "success") location.reload(); });
    }

    function toggleSelectAll() {
        const master = document.getElementById('selectAll');
        document.querySelectorAll('.ticket-checkbox').forEach(cb => cb.checked = master.checked);
    }

    function sendBulkReport() {
        const selected = document.querySelectorAll('.ticket-checkbox:checked');
        if (selected.length === 0) return alert("Select tickets first.");

        let ticketIdList = [];
        let msg = "Summary of Verified Tickets:\n";
        selected.forEach(cb => {
            msg += `- ${cb.value} (${cb.getAttribute('data-name')})\n`;
            ticketIdList.push(cb.value);
        });

        const fd = new FormData();
        fd.append('action', 'send_report');
        fd.append('type', 'Bulk Verification');
        fd.append('message', msg);
        fd.append('ticketIds', ticketIdList.join(',')); 

        fetch(targetURL, { method: 'POST', body: fd })
        .then(res => res.text()).then(data => {
            if(data.trim() === "success") {
                alert("Report sent to Admin. Tickets removed from station dashboard.");
                location.reload(); 
            }
        });
    }

    function sendGeneralReport() {
        const type = document.getElementById('reportType').value;
        const msg = document.getElementById('reportMessage').value;
        if(!msg) return alert("Enter report message.");

        const formData = new FormData();
        formData.append('action', 'send_report');
        formData.append('type', type);
        formData.append('message', msg);

        fetch(targetURL, { method: 'POST', body: formData })
        .then(res => res.text()).then(data => {
            if(data.trim() === "success") {
                alert("Report Sent to Admin.");
                document.getElementById('reportMessage').value = "";
            }
        });
    }

    function renderBuses() {
        const assignments = <?php 
            $res = $conn->query("SELECT plate_number as bus, CONCAT(start_point, ' - ', end_point) as route FROM assignments JOIN routes ON assignments.route_id = routes.id JOIN buses ON assignments.bus_id = buses.id");
            $data = []; if($res) { while($row = $res->fetch_assoc()) $data[] = $row; }
            echo json_encode($data);
        ?>;
        const container = document.getElementById('busStatusList');
        const statuses = JSON.parse(localStorage.getItem('obscs_bus_status')) || {};
        container.innerHTML = assignments.map(a => `
            <div style="display:flex; justify-content:space-between; align-items:center; padding:10px; border-bottom:1px solid #eee;">
                <span><b>${a.bus}</b> (${a.route})</span>
                <span style="color:var(--staff-green)"><b>${statuses[a.bus] ? statuses[a.bus].status : 'Ready'}</b></span>
                <button class="btn btn-blue" style="padding:5px 10px" onclick="updateBus('${a.bus}', 'Arrived')">Mark Arrived</button>
            </div>
        `).join('');
    }

    function updateBus(plate, status) {
        const statuses = JSON.parse(localStorage.getItem('obscs_bus_status')) || {};
        statuses[plate] = { status: status, updatedAt: new Date().toLocaleTimeString() };
        localStorage.setItem('obscs_bus_status', JSON.stringify(statuses));
        renderBuses();
    }

    window.onload = renderBuses;
</script>
</body>
</html>