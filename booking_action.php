<?php
include 'db_config.php';
session_start();

$action = $_GET['action'] ?? '';

// 1. SAVE NEW BOOKING
if ($action == 'add') {
    $ticketId = "TKT-" . rand(100000, 999999);
    $assignId = $_POST['tripId'];
    $user = $_POST['username'];
    $seat = $_POST['seat'];
    $transId = $_POST['transId'];

    $stmt = $conn->prepare("INSERT INTO bookings (ticket_id, assignment_id, passenger_username, seat_number, transaction_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sisss", $ticketId, $assignId, $user, $seat, $transId);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "ticket" => $ticketId]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
}

// 2. FETCH OCCUPIED SEATS FOR A TRIP
if ($action == 'get_occupied') {
    $tripId = $_GET['tripId'];
    $res = $conn->query("SELECT seat_number FROM bookings WHERE assignment_id = '$tripId' AND status != 'Cancelled'");
    $seats = [];
    while($row = $res->fetch_assoc()) { $seats[] = $row['seat_number']; }
    echo json_encode($seats);
}

// 3. FETCH USER HISTORY
if ($action == 'get_history') {
    $user = $_GET['username'];
    // Joining with assignments to get Route/Time/Bus details
    $sql = "SELECT b.*, a.departure_time as time, a.price, b.seat_number as seat, 
            r.start_point, r.end_point, v.plate_number as bus
            FROM bookings b
            JOIN assignments a ON b.assignment_id = a.id
            JOIN routes r ON a.route_id = r.id
            JOIN buses v ON a.bus_id = v.id
            WHERE b.passenger_username = '$user'
            ORDER BY b.id DESC";
    
    $res = $conn->query($sql);
    $history = [];
    while($row = $res->fetch_assoc()) {
        $row['route'] = $row['start_point'] . " - " . $row['end_point'];
        $history[] = $row;
    }
    echo json_encode($history);
}
?>