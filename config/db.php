<?php
$host = "localhost";
$user = "root";
$pass = "";
$db_name = "rekberinaja_db";

$conn = new mysqli($host, $user, $pass, $db_name);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "Koneksi gagal: " . $conn->connect_error]));
}
?>
