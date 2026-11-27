<?php
// Proteksi halaman: memastikan hanya admin yang sudah login dapat mengakses halaman.
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['id_admin'])) {
    header('Location: login.php');
    exit;
}
?>
