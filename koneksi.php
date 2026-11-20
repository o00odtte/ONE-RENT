
<?php
$host = "localhost";
$username = "root";
$password = "626824namu";
$database = "mbdminiproject";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Koneksi database gagal. Periksa konfigurasi koneksi.php dan pastikan MySQL aktif.");
}

mysqli_set_charset($conn, "utf8mb4");
?>
