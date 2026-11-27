<?php
// LOGOUT: mengakhiri sesi admin dan mengarahkan pengguna kembali ke halaman login.
session_start();
session_unset();
session_destroy();

header('Location: login.php');
exit;
?>