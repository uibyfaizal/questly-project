<?php
$host = "localhost";
$user = "root"; // ganti sesuai settingmu
$pass = "";
$db   = "questly"; // nama database

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
?>
