<?php
include 'db_config.php';

if (isset($_GET['type']) && isset($_GET['id'])) {
    $type = $_GET['type'];
    $id = $_GET['id'];

    if ($type == 'user') {
        $sql = "DELETE FROM users WHERE id = $id";
    } elseif ($type == 'bus') {
        $sql = "DELETE FROM buses WHERE id = $id";
    } elseif ($type == 'route') {
        $sql = "DELETE FROM routes WHERE id = $id";
    } elseif ($type == 'assign') {
        $sql = "DELETE FROM assignments WHERE id = $id";
    } elseif ($type == 'report') {
        $sql = "DELETE FROM reports WHERE id = $id";
    }

    if (isset($sql)) {
        if ($conn->query($sql)) {
            header("Location: admin.php?msg=Item deleted successfully");
            exit();
        } else {
            echo "Error deleting record: " . $conn->error;
        }
    }
}
?>