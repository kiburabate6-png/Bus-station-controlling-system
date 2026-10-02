<?php 
include 'db_config.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- PHP BACKEND LOGIC (AJAX Handlers) ---

// 1. Save Booking
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'save_booking') {
    $ticketId = $_POST['ticketId'];
    $tripId = $_POST['tripId'];
    $pName = $_POST['passenger'];
    $pUser = $_POST['passengerUser'];
    $route = $_POST['route'];
    $time = $_POST['time'];
    $bus = $_POST['bus'];
    $seat = $_POST['seat'];
    $price = $_POST['price'];
    $transId = $_POST['transId'];
    $date = $_POST['date']; // This is the travel date from assignment
    $status = "Pending Verification";

    $stmt = $conn->prepare("INSERT INTO bookings (ticketId, tripId, passengerName, passengerUser, route, departureTime, busPlate, seatNumber, price, transId, bookingDate, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sisssssiisss", $ticketId, $tripId, $pName, $pUser, $route, $time, $bus, $seat, $price, $transId, $date, $status);
    
    if ($stmt->execute()) {
        echo json_encode(["status" => "success"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
    exit; 
}

// 2. Fetch Occupied Seats
if (isset($_GET['action']) && $_GET['action'] == 'get_seats') {
    $tripId = (int)$_GET['tripId'];
    $result = $conn->query("SELECT seatNumber FROM bookings WHERE tripId = $tripId AND status != 'Cancelled'");
    $occupied = [];
    while($row = $result->fetch_assoc()) { $occupied[] = (int)$row['seatNumber']; }
    echo json_encode($occupied);
    exit;
}

// 3. Get History
if (isset($_GET['action']) && $_GET['action'] == 'get_history') {
    $username = mysqli_real_escape_string($conn, $_GET['username']);
    $result = $conn->query("SELECT * FROM bookings WHERE passengerUser = '$username' ORDER BY id DESC");
    $history = [];
    while($row = $result->fetch_assoc()) { $history[] = $row; }
    echo json_encode($history);
    exit;
}

// 4. Update Profile
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_profile') {
    $username = $_POST['username'];
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $sql = "UPDATE users SET fullname = '$fullname' WHERE username = '$username'";
    if ($conn->query($sql)) {
        echo json_encode(["status" => "success", "newName" => $fullname]);
    } else {
        echo json_encode(["status" => "error"]);
    }
    exit;
}

// 5. Fetch Assignments (Added a.travel_date and date filter)
$assignments = [];
$assignResult = $conn->query("SELECT a.id, CONCAT(r.start_point, ' - ', r.end_point) as route, a.travel_date, a.departure_time as time, b.plate_number as bus, b.capacity as cap, a.price 
                             FROM assignments a 
                             JOIN routes r ON a.route_id = r.id 
                             JOIN buses b ON a.bus_id = b.id
                             WHERE a.travel_date >= CURDATE()
                             ORDER BY a.travel_date ASC");

if ($assignResult) {
    while($row = $assignResult->fetch_assoc()) { $assignments[] = $row; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OBSCS - Passenger Dashboard</title>
    <style>
        :root { --primary: #2c3e50; --accent: #3498db; --success: #27ae60; --danger: #e74c3c; --sidebar-width: 260px; }
        body { font-family: 'Segoe UI', sans-serif; margin: 0; display: flex; background: #f4f7f6; }
        .sidebar { width: var(--sidebar-width); height: 100vh; background: var(--primary); color: white; padding: 20px; position: fixed; box-sizing: border-box; z-index: 100; }
        .sidebar-pic { width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; margin-bottom: 10px; background: #eee; }
        .sidebar a { display: block; color: #bdc3c7; padding: 15px 10px; text-decoration: none; cursor: pointer; border-radius: 5px; margin-bottom: 5px; transition: 0.3s; }
        .sidebar a:hover { background: #34495e; color: white; }
        .sidebar .active { background: var(--accent); }
        .main { margin-left: var(--sidebar-width); padding: 40px; width: calc(100% - var(--sidebar-width)); box-sizing: border-box; }
        .section { display: none; max-width: 900px; margin: 0 auto; animation: fadeIn 0.4s ease; }
        .active-section { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); margin-bottom: 25px; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; color: white; transition: 0.3s; }
        .btn-primary { background: var(--accent); }
        .btn-success { background: var(--success); }
        .btn-danger { background: var(--danger); }
        .seat-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; padding: 10px; max-width: 300px; margin: 0 auto; }
        .seat { width: 45px; height: 45px; background: #fff; border: 2px solid #bdc3c7; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; font-weight: bold; }
        .seat.occupied { background: #d5dbdb; color: #7f8c8d; cursor: not-allowed; border: none; }
        .seat.selected { background: var(--success); color: white; }
        .plate-badge { background: #eee; padding: 2px 6px; border-radius: 4px; font-family: monospace; color: #2c3e50; border: 1px solid #ccc; font-weight: bold; }
        .trip-info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.9rem; color: #555; margin-top: 10px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div style="text-align: center; padding-bottom: 20px;">
            <img src="https://via.placeholder.com/60" id="sideProfilePic" class="sidebar-pic">
            <h2 style="margin: 0; font-size: 1.2rem;">OBSCS</h2>
            <p id="pNameDisp" style="color: #bdc3c7; font-size: 1rem; font-weight: bold; margin: 8px 0 0 0;"></p>
        </div>
        <hr style="border: 0; border-top: 0.5px solid #444; margin-bottom: 20px;">
        <a onclick="showSection('search')" id="nav-search" class="active">🔍 Search & Book</a>
        <a onclick="showSection('myBookings')" id="nav-myBookings">🎟️ My Bookings</a>
        <a onclick="showSection('profile')" id="nav-profile">👤 My Profile</a>
        <a href="logout.php" style="margin-top: 50px; color: var(--danger);">🚪 Logout</a>
    </div>

    <div class="main">
        <!-- SEARCH SECTION -->
        <div id="search" class="section active-section">
            <h1>Available Bus Trips</h1>
            <div class="card">
                <div style="display:flex; gap:10px; margin-bottom: 15px;">
                    <input type="text" id="fromIn" placeholder="From (Departure)" style="width: 100%; padding:12px; border:1px solid #ddd; border-radius:8px;">
                    <input type="text" id="toIn" placeholder="To (Destination)" style="width: 100%; padding:12px; border:1px solid #ddd; border-radius:8px;">
                </div>
                <button class="btn btn-primary" style="width:100%;" onclick="searchTrips()">Search Available Buses</button>
            </div>
            <div id="searchResults"></div>
        </div>

        <!-- SEAT SELECTION & BOOKING FLOW -->
        <div id="bookingFlow" class="section">
            <div class="card" style="text-align: center;">
                <h1 id="bookRoute" style="margin-bottom: 5px;"></h1>
                <div id="tripDetailsDisplay" style="margin-bottom: 20px; font-weight: bold; color: #3498db;"></div>
                <hr>
                <p style="font-size: 0.8rem; color: #7f8c8d;">Front of Bus (Driver)</p>
                <div class="seat-grid" id="seatGrid"></div>
                
                <div id="paymentStep" style="display:none; margin-top: 25px; border-top: 1px solid #eee; padding-top: 20px;">
                    <p><b>Fare:</b> <span id="fareDisplay" style="color: var(--success); font-size: 1.2rem;"></span> ETB</p>
                    <p><b>Selected Seat:</b> <span id="seatDisplay" style="font-weight: bold;"></span></p>
                    <input type="text" id="transId" placeholder="Enter Transaction Reference (CBE/TeleBirr)" style="width: 80%; padding:12px; border:1px solid #ddd; border-radius:8px; margin-bottom: 15px;">
                    <button class="btn btn-success" style="width:80%;" onclick="confirmBooking()">Complete Booking</button>
                </div>
                <button class="btn btn-danger" style="margin-top:20px; width:80%" onclick="showSection('search')">Cancel and Go Back</button>
            </div>
        </div>

        <div id="myBookings" class="section">
            <h1>My Personal Tickets</h1>
            <div id="ticketList"></div>
        </div>

        <div id="profile" class="section">
            <h1>Profile Settings</h1>
            <div class="card" style="text-align: center;">
                <input type="text" id="profName" placeholder="Full name" style="width: 80%; padding:12px; border:1px solid #ddd; border-radius:8px; margin-bottom: 15px;">
                <button class="btn btn-success" style="width:80%;" onclick="saveProfile()">Update Profile</button>
            </div>
        </div>
    </div>

    <script>
        const dbAssignments = <?php echo json_encode($assignments); ?>;
        const userData = localStorage.getItem('currentUser');
        const user = userData ? JSON.parse(userData) : null;
        let selectedTrip = null;
        let selectedSeat = null;

        if (!user || user.role !== 'Passenger') window.location.href = 'login.php';

        window.onload = () => { 
            document.getElementById('pNameDisp').innerText = user.name;
            document.getElementById('profName').value = user.name;
            searchTrips(); // Show all on load
        };

        function showSection(id) {
            document.querySelectorAll('.section').forEach(s => s.classList.remove('active-section'));
            document.querySelectorAll('.sidebar a').forEach(n => n.classList.remove('active'));
            document.getElementById(id).classList.add('active-section');
            const nav = document.getElementById('nav-' + id);
            if (nav) nav.classList.add('active');
            if(id === 'myBookings') renderHistory();
        }

        function searchTrips() {
            const from = document.getElementById('fromIn').value.toLowerCase();
            const to = document.getElementById('toIn').value.toLowerCase();
            const container = document.getElementById('searchResults');
            
            const filtered = dbAssignments.filter(t => t.route.toLowerCase().includes(from) && t.route.toLowerCase().includes(to));

            if(filtered.length === 0) {
                container.innerHTML = '<div class="card"><p style="text-align:center;">No buses available for this route.</p></div>';
                return;
            }
            
            container.innerHTML = filtered.map(t => `
                <div class="card">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="flex:1;">
                            <h3 style="margin:0 0 10px 0;">${t.route}</h3>
                            <div class="trip-info-grid">
                                <span>📅 Date: <b>${t.travel_date}</b></span>
                                <span>🕒 Time: <b>${t.time}</b></span>
                                <span>🚌 Bus Plate: <span class="plate-badge">${t.bus}</span></span>
                                <span>💰 Price: <b>${t.price} ETB</b></span>
                            </div>
                        </div>
                        <button class="btn btn-primary" onclick="openBooking(${t.id})">Book Now</button>
                    </div>
                </div>`).join('');
        }

        async function openBooking(tripId) {
            selectedTrip = dbAssignments.find(t => t.id == tripId);
            const response = await fetch(`passenger.php?action=get_seats&tripId=${tripId}`);
            const occupiedSeats = await response.json();
            
            showSection('bookingFlow');
            document.getElementById('bookRoute').innerText = selectedTrip.route;
            document.getElementById('tripDetailsDisplay').innerText = `${selectedTrip.travel_date} at ${selectedTrip.time}`;
            document.getElementById('fareDisplay').innerText = selectedTrip.price;
            document.getElementById('paymentStep').style.display = 'none';

            const grid = document.getElementById('seatGrid');
            grid.innerHTML = "";
            for(let i=1; i <= parseInt(selectedTrip.cap); i++) {
                const isTaken = occupiedSeats.includes(i);
                const s = document.createElement('div');
                s.className = isTaken ? "seat occupied" : "seat";
                s.innerText = i;
                if(!isTaken) s.onclick = () => {
                    document.querySelectorAll('.seat').forEach(x => x.classList.remove('selected'));
                    s.classList.add('selected');
                    selectedSeat = i;
                    document.getElementById('seatDisplay').innerText = i;
                    document.getElementById('paymentStep').style.display = 'block';
                };
                grid.appendChild(s);
            }
        }

        function confirmBooking() {
            const transId = document.getElementById('transId').value.trim();
            if(!transId) return alert("Please enter the transaction reference from your bank.");
            
            const fd = new FormData();
            fd.append('action', 'save_booking');
            fd.append('ticketId', "TKT-" + Math.floor(Math.random()*90000 + 10000));
            fd.append('tripId', selectedTrip.id);
            fd.append('passenger', user.name);
            fd.append('passengerUser', user.username);
            fd.append('route', selectedTrip.route);
            fd.append('time', selectedTrip.time);
            fd.append('bus', selectedTrip.bus);
            fd.append('seat', selectedSeat);
            fd.append('price', selectedTrip.price);
            fd.append('transId', transId);
            fd.append('date', selectedTrip.travel_date); // Pass the travel date

            fetch('passenger.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
                if(data.status === 'success') { 
                    alert("Booking Submitted! Waiting for station verification."); 
                    showSection('myBookings'); 
                }
            });
        }

        async function renderHistory() {
            const res = await fetch(`passenger.php?action=get_history&username=${user.username}`);
            const data = await res.json();
            const list = document.getElementById('ticketList');
            list.innerHTML = data.length ? data.map(b => `
                <div class="card" style="border-left: 5px solid ${b.status === 'Pending Verification' ? '#e67e22' : '#27ae60'};">
                    <small>ID: ${b.ticketId}</small>
                    <h3>${b.route}</h3>
                    <p>📅 Trip Date: <b>${b.bookingDate}</b> | Seat: <b>${b.seatNumber}</b> | Bus: ${b.busPlate}</p>
                    <b>Status: ${b.status}</b>
                </div>`).join('') : '<p style="text-align:center;">No bookings yet.</p>';
        }

        function saveProfile() {
            const newName = document.getElementById('profName').value;
            const fd = new FormData();
            fd.append('action', 'update_profile');
            fd.append('fullname', newName); fd.append('username', user.username);
            fetch('passenger.php', { method: 'POST', body: fd }).then(res => res.json()).then(data => {
                if(data.status === 'success') {
                    user.name = data.newName;
                    localStorage.setItem('currentUser', JSON.stringify(user));
                    alert("Profile Updated!"); location.reload();
                }
            });
        }
    </script>
</body>
</html>