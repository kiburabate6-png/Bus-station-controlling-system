<?php
include 'db_config.php'; // ዳታቤዝ ኮንፊገሬሽን
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 1. ከ JavaScript የተላኩትን ስሞች በትክክል መቀበል
    $sender  = isset($_POST['sender'])  ? trim($_POST['sender'])  : 'Unknown';
    $type    = isset($_POST['type'])    ? trim($_POST['type'])    : 'General';
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';
    $bus     = isset($_POST['bus'])     ? trim($_POST['bus'])     : 'N/A';

    if (!empty($message)) {
        // 2. ዳታቤዝ ውስጥ ማስገባት (INSERT)
        // የሰንጠረዡ ስም 'reports' መሆኑን እና ኮለምኖቹ (sender_name, report_type, message, bus_plate) መሆናቸውን አረጋግጥ
        $stmt = $conn->prepare("INSERT INTO reports (sender_name, report_type, message, bus_plate) VALUES (?, ?, ?, ?)");
        
        if ($stmt) {
            $stmt->bind_param("ssss", $sender, $type, $message, $bus);

            if ($stmt->execute()) {
                echo "success"; // ለ JavaScript "success" ብሎ ይመልሳል
            } else {
                echo "Database Error: " . $stmt->error;
            }
            $stmt->close();
        } else {
            echo "Preparation Error: " . $conn->error;
        }
    } else {
        echo "Empty message received";
    }
    $conn->close();
}
?>