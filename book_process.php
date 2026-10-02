<?php
include 'db.php';

if (isset($_POST['confirm_booking'])) {
    $trip_id = $_POST['trip_id'];
    $seat_num = $_POST['seat_num'];
    $p_id = $_SESSION['user_id'];
    $p_name = $_SESSION['fullname'];
    $ticket_id = "TKT-" . rand(1000, 9999);

    // Double check seat availability
    $check = mysqli_query($conn, "SELECT id FROM bookings WHERE trip_id = '$trip_id' AND seat_number = '$seat_num'");
    
    if (mysqli_num_rows($check) > 0) {
        echo "<script>alert('Seat already taken!'); window.location.href='passenger.php';</script>";
    } else {
        $sql = "INSERT INTO bookings (ticket_id, trip_id, passenger_id, passenger_name, seat_number, status) 
                VALUES ('$ticket_id', '$trip_id', '$p_id', '$p_name', '$seat_num', 'Pending Verification')";
        
        if (mysqli_query($conn, $sql)) {
            echo "<script>alert('Booking Successful!'); window.location.href='passenger.php?sec=myBookings';</script>";
        }
    }
}
?>