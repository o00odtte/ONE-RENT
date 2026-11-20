<?php
require_once 'auth.php';
require_once 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Mengamankan output HTML
function e($data) {
    return htmlspecialchars($data ?? "", ENT_QUOTES, "UTF-8");
}

$pesan = "";
$error = "";

// Validasi nomor HP
function formatHP($nomor) {
    $nomor = preg_replace('/\s+/', '', trim($nomor));

    if (!preg_match('/^[0-9]{10,15}$/', $nomor)) {
        return false;
    }

    return $nomor;
}

// Validasi nomor KTP
function formatKTP($nomor) {
    $nomor = trim($nomor);

    if (!preg_match('/^[0-9]{16}$/', $nomor)) {
        return false;
    }

    return $nomor;
}

// Validasi nomor SIM A
function formatSIM($nomor) {
    $nomor = trim($nomor);

    if (!preg_match('/^[0-9]{16}$/', $nomor)) {
        return false;
    }

    return $nomor;
}


// =====================================
// PROSES TAMBAH PELANGGAN
// =====================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["tambah_pelanggan"])) {

    $nama = trim($_POST["nama_penyewa"] ?? "");
    $hp = formatHP($_POST["no_hp"] ?? "");
    $ktp = formatKTP($_POST["no_KTP"] ?? "");
    $sim = formatSIM($_POST["no_simA"] ?? "");
    $tanggal = trim($_POST["tanggal_lahir"] ?? "");
    $alamat = trim($_POST["alamat"] ?? "");

    if ($tanggal === "") {
        $tanggal = null;
    }

    if (
        $nama === "" ||
        mb_strlen($nama) > 60 ||
        $hp === false ||
        $ktp === false ||
        $sim === false ||
        $tanggal === null
    ) {
        $error = "Semua data wajib diisi dengan format yang benar.";
    } elseif (!preg_match('/[a-zA-Z]/', $alamat) || mb_strlen($alamat) > 500) {
        $error = "Alamat harus diisi dengan alamat yang valid.";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || !checkdate((int)substr($tanggal,5,2), (int)substr($tanggal,8,2), (int)substr($tanggal,0,4))) {
        $error = "Tanggal lahir tidak valid.";
    } elseif ($tanggal > date("Y-m-d")) {
        $error = "Tanggal lahir tidak boleh melebihi hari ini.";
    } else {

        // Cek nomor KTP dan SIM A
        $cek = mysqli_prepare($conn, "
            SELECT id_penyewa
            FROM penyewa
            WHERE no_KTP = ? OR no_simA = ?
        ");

        mysqli_stmt_bind_param($cek, "ss", $ktp, $sim);
        mysqli_stmt_execute($cek);
        $hasilCek = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasilCek) > 0) {

            $error = "Nomor KTP atau SIM A sudah terdaftar.";

        } else {

            // Membuat ID pelanggan otomatis
            $queryID = mysqli_query($conn, "
                SELECT MAX(CAST(SUBSTRING(id_penyewa, 4) AS UNSIGNED)) AS nomor
                FROM penyewa
                WHERE id_penyewa REGEXP '^PNY[0-9]+$'
            ");

            $dataID = mysqli_fetch_assoc($queryID);
            $nomorBaru = (int) ($dataID["nomor"] ?? 0) + 1;
            $idPenyewa = "PNY" . str_pad($nomorBaru, 3, "0", STR_PAD_LEFT);

            $stmt = mysqli_prepare($conn, "
                INSERT INTO penyewa
                (id_penyewa, nama_penyewa, no_hp, no_KTP, no_simA, tanggal_lahir, alamat)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "sssssss",
                $idPenyewa,
                $nama,
                $hp,
                $ktp,
                $sim,
                $tanggal,
                $alamat
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: pelanggan.php?berhasil=1");
                exit;
            } else {
                $error = "Gagal menambahkan pelanggan.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($cek);
    }
}


// =====================================
// PROSES EDIT PELANGGAN
// =====================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["edit_pelanggan"])) {

    $idPenyewa = trim($_POST["id_penyewa"] ?? "");
    $nama = trim($_POST["nama_penyewa"] ?? "");
    $hp = formatHP($_POST["no_hp"] ?? "");
    $ktp = formatKTP($_POST["no_KTP"] ?? "");
    $sim = formatSIM($_POST["no_simA"] ?? "");
    $tanggal = trim($_POST["tanggal_lahir"] ?? "");
    $alamat = trim($_POST["alamat"] ?? "");

    if ($tanggal === "") {
        $tanggal = null;
    }

    if (
        $idPenyewa === "" ||
        $nama === "" ||
        mb_strlen($nama) > 60 ||
        $hp === false ||
        $ktp === false ||
        $sim === false ||
        $tanggal === null
    ) {
        $error = "Semua data wajib diisi dengan format yang benar.";
    } elseif (!preg_match('/[a-zA-Z]/', $alamat) || mb_strlen($alamat) > 500) {
        $error = "Alamat harus diisi dengan alamat yang valid.";
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || !checkdate((int)substr($tanggal,5,2), (int)substr($tanggal,8,2), (int)substr($tanggal,0,4))) {
        $error = "Tanggal lahir tidak valid.";
    } elseif ($tanggal > date("Y-m-d")) {
        $error = "Tanggal lahir tidak boleh melebihi hari ini.";
    } else {

        $cekIdPenyewa = mysqli_prepare($conn, "SELECT id_penyewa FROM penyewa WHERE id_penyewa=?");
        mysqli_stmt_bind_param($cekIdPenyewa, "s", $idPenyewa);
        mysqli_stmt_execute($cekIdPenyewa);
        $idPenyewaResult = mysqli_stmt_get_result($cekIdPenyewa);
        $idPenyewaAda = mysqli_num_rows($idPenyewaResult) > 0;
        mysqli_stmt_close($cekIdPenyewa);
        if (!$idPenyewaAda) { $error = "Data pelanggan tidak ditemukan."; }
        else {
        // Cek KTP dan SIM A milik pelanggan lain
        $cek = mysqli_prepare($conn, "
            SELECT id_penyewa
            FROM penyewa
            WHERE (no_KTP = ? OR no_simA = ?)
            AND id_penyewa != ?
        ");

        mysqli_stmt_bind_param($cek, "sss", $ktp, $sim, $idPenyewa);
        mysqli_stmt_execute($cek);
        $hasilCek = mysqli_stmt_get_result($cek);

        if (mysqli_num_rows($hasilCek) > 0) {

            $error = "Nomor KTP atau SIM A sudah digunakan pelanggan lain.";

        } else {

            $stmt = mysqli_prepare($conn, "
                UPDATE penyewa
                SET nama_penyewa = ?,
                    no_hp = ?,
                    no_KTP = ?,
                    no_simA = ?,
                    tanggal_lahir = ?,
                    alamat = ?
                WHERE id_penyewa = ?
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "sssssss",
                $nama,
                $hp,
                $ktp,
                $sim,
                $tanggal,
                $alamat,
                $idPenyewa
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: pelanggan.php?diedit=1");
                exit;
            } else {
                $error = "Gagal memperbarui data pelanggan.";
            }

            mysqli_stmt_close($stmt);
        }

        mysqli_stmt_close($cek);
        }
    }
}


// =====================================
// PROSES HAPUS PELANGGAN
// =====================================
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["hapus_pelanggan"])) {

    $idPenyewa = trim($_POST["hapus_id_penyewa"] ?? "");

    if ($idPenyewa === "") {

        $error = "ID pelanggan tidak ditemukan.";

    } else {

        $stmt = mysqli_prepare($conn, "
            DELETE FROM penyewa
            WHERE id_penyewa = ?
        ");

        mysqli_stmt_bind_param($stmt, "s", $idPenyewa);

        if (mysqli_stmt_execute($stmt)) {

            if (mysqli_stmt_affected_rows($stmt) > 0) {
                header("Location: pelanggan.php?dihapus=1");
                exit;
            } else {
                $error = "Data pelanggan tidak ditemukan.";
            }

        } else {

            // Pelanggan masih digunakan pada tabel lain
            if (mysqli_stmt_errno($stmt) == 1451) {
                $error = "Pelanggan tidak dapat dihapus karena masih digunakan pada data reservasi atau transaksi.";
            } else {
                $error = "Gagal menghapus data pelanggan.";
            }

        }

        mysqli_stmt_close($stmt);
    }
}


// =====================================
// NOTIFIKASI
// =====================================
if ($_SERVER["REQUEST_METHOD"] === "GET") {

    if (isset($_GET["berhasil"])) {
        $pesan = "Pelanggan berhasil ditambahkan.";
    } elseif (isset($_GET["diedit"])) {
        $pesan = "Data pelanggan berhasil diperbarui.";
    } elseif (isset($_GET["dihapus"])) {
        $pesan = "Data pelanggan berhasil dihapus.";
    }

}


// =====================================
// AMBIL DATA PELANGGAN
// =====================================
$queryPelanggan = mysqli_query($conn, "
    SELECT *
    FROM penyewa
    ORDER BY id_penyewa ASC
");

$totalPelanggan = mysqli_num_rows($queryPelanggan);

?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pelanggan | ONE RENT</title>

    <link rel="stylesheet" href="style.css">

    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/lucide@latest"></script>

    <style>

        body {
            font-family: 'Manrope', sans-serif;
        }

        .customer-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 24px;
        }

        .customer-heading h2 {
            color: var(--white);
            font-size: 22px;
            margin-bottom: 5px;
        }

        .customer-heading p {
            color: #a7abc1;
            font-size: 13px;
        }

        .customer-summary {
            background: var(--card);
            border-radius: 14px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 22px;
            max-width: 300px;
        }

        .customer-summary-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: rgba(249,177,122,.15);
            color: var(--peach);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .customer-summary small {
            color: #c0c2d0;
            font-size: 12px;
        }

        .customer-summary h3 {
            color: white;
            font-size: 23px;
            margin-top: 4px;
        }

        .customer-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .customer-search {
            position: relative;
            flex: 1;
            max-width: 350px;
        }

        .customer-search i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #999fb9;
            width: 17px;
        }

        .customer-search input {
            width: 100%;
            padding: 12px 14px 12px 42px;
            border-radius: 9px;
            border: 1px solid rgba(255,255,255,.1);
            background: #2D3250;
            color: white;
            outline: none;
            font-family: inherit;
        }

        .customer-search input:focus {
            border-color: var(--peach);
        }

        .customer-add-btn {
            background: var(--peach);
            color: #2D3250;
            border: none;
            border-radius: 9px;
            padding: 12px 17px;
            font-weight: 800;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-family: inherit;
        }

        .customer-add-btn:hover {
            opacity: .9;
        }

        .customer-table-wrap {
            overflow-x: auto;
        }

        .customer-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 750px;
        }

        .customer-table th {
            color: #aeb2c8;
            font-size: 12px;
            font-weight: 700;
            text-align: left;
            padding: 15px 13px;
            background: rgba(45,50,80,.6);
        }

        .customer-table td {
            color: #f2f2f7;
            font-size: 13px;
            padding: 16px 13px;
            border-bottom: 1px solid rgba(255,255,255,.07);
        }

        .customer-table tr:hover td {
            background: rgba(255,255,255,.025);
        }

        .customer-id {
            color: var(--peach);
            font-weight: 700;
        }

        .customer-actions {
            display: flex;
            gap: 8px;
        }

        .customer-action {
            border: none;
            width: 33px;
            height: 33px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            background: transparent;
        }

        .customer-action i {
            width: 16px;
        }

        .customer-detail-btn {
            color: #75a9e0;
            background: rgba(57,121,184,.14);
        }

        .customer-edit-btn {
            color: #e5a45f;
            background: rgba(217,139,66,.14);
        }

        .customer-delete-btn {
            color: #e47777;
            background: rgba(217,83,79,.14);
        }

        .customer-action:hover {
            filter: brightness(1.2);
        }

        .customer-empty {
            text-align: center;
            padding: 35px !important;
            color: #aeb2c8 !important;
        }

        .customer-modal {
            position: fixed;
            inset: 0;
            background: rgba(10,12,25,.72);
            backdrop-filter: blur(5px);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 999;
            padding: 20px;
        }

        .customer-modal.show {
            display: flex;
        }

        .customer-modal-card {
            width: 100%;
            max-width: 650px;
            max-height: 90vh;
            overflow-y: auto;
            background: #424769;
            border-radius: 16px;
            padding: 26px;
            box-shadow: 0 20px 60px rgba(0,0,0,.35);
        }

        .customer-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 23px;
        }

        .customer-modal-header h3 {
            color: white;
            font-size: 19px;
        }

        .customer-close {
            background: transparent;
            color: #c6c8d7;
            border: none;
            cursor: pointer;
        }

        .customer-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 17px;
        }

        .customer-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .customer-field.full {
            grid-column: 1 / -1;
        }

        .customer-field label {
            color: #e1e2ec;
            font-size: 12px;
            font-weight: 700;
        }

        .customer-field input,
        .customer-field textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid rgba(255,255,255,.12);
            background: #2D3250;
            border-radius: 8px;
            color: white;
            font-family: inherit;
            font-size: 13px;
            outline: none;
        }

        .customer-field input:focus,
        .customer-field textarea:focus {
            border-color: var(--peach);
        }

        .customer-field textarea {
            resize: vertical;
            min-height: 75px;
        }

        .customer-field input::placeholder,
        .customer-field textarea::placeholder {
            color: #9298b1;
        }

        .customer-form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 24px;
        }

        .customer-cancel,
        .customer-save {
            padding: 11px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
        }

        .customer-cancel {
            background: #555b7d;
            color: white;
        }

        .customer-save {
            background: var(--peach);
            color: #2D3250;
        }

        .customer-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .customer-detail-item {
            background: #2D3250;
            padding: 14px;
            border-radius: 9px;
            min-width: 0;
        }

        .customer-detail-item small {
            color: #aeb2c8;
            display: block;
            margin-bottom: 7px;
            font-size: 11px;
        }

        .customer-detail-item p {
            color: white;
            font-size: 13px;
            overflow-wrap: anywhere;
        }

        .customer-alert {
            padding: 13px 16px;
            border-radius: 9px;
            margin-bottom: 18px;
            font-size: 13px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .customer-alert.success {
            color: #a6e5c3;
            background: rgba(85,184,137,.14);
            border: 1px solid rgba(85,184,137,.25);
        }

        .customer-alert.error {
            color: #ffb1b1;
            background: rgba(217,83,79,.14);
            border: 1px solid rgba(217,83,79,.25);
        }

        .customer-alert button {
            background: transparent;
            border: none;
            color: inherit;
            cursor: pointer;
        }

        /* MODAL HAPUS */
        .customer-delete-card {
            max-width: 430px;
            text-align: center;
        }

        .customer-delete-icon {
            width: 65px;
            height: 65px;
            margin: 0 auto 18px;
            border-radius: 50%;
            background: rgba(217,83,79,.15);
            color: #e47777;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .customer-delete-icon i {
            width: 30px;
            height: 30px;
        }

        .customer-delete-card h3 {
            color: white;
            font-size: 19px;
            margin-bottom: 10px;
        }

        .customer-delete-card p {
            color: #c0c2d0;
            font-size: 13px;
            line-height: 1.7;
        }

        .customer-delete-name {
            color: #ffb1b1;
            font-weight: 800;
        }

        .customer-delete-warning {
            background: rgba(217,83,79,.12);
            border: 1px solid rgba(217,83,79,.2);
            border-radius: 9px;
            padding: 11px;
            margin-top: 15px;
            color: #ffb1b1;
            font-size: 11px;
            line-height: 1.6;
        }

        .customer-delete-actions {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 23px;
        }

        .customer-delete-confirm {
            background: #D9534F;
            color: white;
            padding: 11px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
        }

        .customer-delete-confirm:hover {
            background: #c9433f;
        }

        @media(max-width: 650px) {

            .customer-heading {
                align-items: flex-start;
                flex-direction: column;
            }

            .customer-toolbar {
                align-items: stretch;
            }

            .customer-search {
                max-width: none;
            }

            .customer-add-btn {
                justify-content: center;
            }

            .customer-form-grid,
            .customer-detail-grid {
                grid-template-columns: 1fr;
            }

            .customer-field.full {
                grid-column: auto;
            }

            .customer-modal-card {
                padding: 20px;
            }

        }

    </style>
</head>


<body>

<div class="container">

    <!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">

    <div class="brand">
        <div class="brand-icon">
            <i data-lucide="car-front"></i>
        </div>
        <div>
            <h2>ONE RENT</h2>
            <p>CAR RENTAL MANAGEMENT</p>
        </div>
    </div>

    <div class="menu-title">MENU UTAMA</div>

    <nav class="menu">

        <a href="dashboard.php" class="menu-item">
            <i data-lucide="layout-dashboard"></i>
            Dashboard
        </a>

        <a href="mobil.php" class="menu-item">
            <i data-lucide="car-front"></i>
            Data Mobil
        </a>

        <a href="pelanggan.php" class="menu-item active">
            <i data-lucide="users"></i>
            Data Pelanggan
        </a>

        <a href="reservasi.php" class="menu-item">
            <i data-lucide="calendar-check"></i>
            Reservasi
        </a>

        <a href="transaksi.php" class="menu-item">
            <i data-lucide="clipboard-list"></i>
            Transaksi Sewa
        </a>

        <a href="pembayaran.php" class="menu-item">
            <i data-lucide="wallet"></i>
            Pembayaran
        </a>

        <a href="pengembalian.php" class="menu-item">
            <i data-lucide="undo-2"></i>
            Pengembalian
        </a>

        <a href="denda.php" class="menu-item">
            <i data-lucide="receipt"></i>
            Denda
        </a>

    </nav>

    <div class="menu-title">PENGATURAN</div>

    <nav class="menu">
        <a href="profil.php" class="menu-item">
            <i data-lucide="settings"></i>
            Profil Admin
        </a>
    </nav>

    <div class="sidebar-bottom">

        <div class="admin-mini">
            <div class="avatar"><?= e(strtoupper(substr($_SESSION["nama_admin"] ?? "A", 0, 1))) ?></div>
            <div>
                <strong><?= e($_SESSION["nama_admin"] ?? "Administrator") ?></strong>
                <small>Admin</small>
            </div>
        </div>

        <a href="logout.php" class="logout">
            <i data-lucide="log-out"></i>
            Keluar
        </a>

    </div>

</aside>


    <!-- MAIN CONTENT -->
    <main class="main-content">

        <header class="topbar">

    <button class="menu-toggle" onclick="toggleSidebar()">
        <i data-lucide="menu"></i>
    </button>

    <div class="page-heading">
        <h2>Data Pelanggan</h2>
        <p>Kelola informasi pelanggan ONE RENT.</p>
    </div>

</header>


        <section class="content">

            <!-- NOTIFIKASI BERHASIL -->
            <?php if ($pesan !== ""): ?>

                <div class="customer-alert success">
                    <span><?= e($pesan) ?></span>

                    <button onclick="this.parentElement.remove()">
                        <i data-lucide="x"></i>
                    </button>
                </div>

            <?php endif; ?>


            <!-- NOTIFIKASI ERROR -->
            <?php if ($error !== ""): ?>

                <div class="customer-alert error">
                    <span><?= e($error) ?></span>

                    <button onclick="this.parentElement.remove()">
                        <i data-lucide="x"></i>
                    </button>
                </div>

            <?php endif; ?>


            <div class="customer-heading">

                <div>
                    <h2>Daftar Pelanggan</h2>
                    <p>Informasi pelanggan yang terdaftar dalam sistem.</p>
                </div>

                <button class="customer-add-btn" onclick="openAddModal()">
                    <i data-lucide="plus"></i>
                    Tambah Pelanggan
                </button>

            </div>


            <!-- TOTAL PELANGGAN -->
            <div class="customer-summary">

                <div class="customer-summary-icon">
                    <i data-lucide="users"></i>
                </div>

                <div>
                    <small>Total Pelanggan Terdaftar</small>
                    <h3><?= $totalPelanggan ?></h3>
                </div>

            </div>


            <!-- TABEL PELANGGAN -->
            <div class="panel">

                <div class="customer-toolbar">

                    <div class="customer-search">

                        <i data-lucide="search"></i>

                        <input
                            type="text"
                            id="searchPelanggan"
                            placeholder="Cari nama, ID, atau nomor HP..."
                        >

                    </div>

                </div>


                <div class="customer-table-wrap">

                    <table class="customer-table" id="tablePelanggan">

                        <thead>
                            <tr>
                                <th>ID PELANGGAN</th>
                                <th>NAMA PELANGGAN</th>
                                <th>NO. HP</th>
                                <th>NO. KTP</th>
                                <th>AKSI</th>
                            </tr>
                        </thead>


                        <tbody>

                        <?php if ($totalPelanggan > 0): ?>

                            <?php while ($row = mysqli_fetch_assoc($queryPelanggan)): ?>

                                <tr>

                                    <td class="customer-id">
                                        <?= e($row["id_penyewa"]) ?>
                                    </td>

                                    <td>
                                        <?= e($row["nama_penyewa"]) ?>
                                    </td>

                                    <td>
                                        <?= e($row["no_hp"]) ?>
                                    </td>

                                    <td>
                                        <?= e($row["no_KTP"]) ?>
                                    </td>

                                    <td>

                                        <div class="customer-actions">

                                            <!-- DETAIL -->
                                            <button
                                                type="button"
                                                class="customer-action customer-detail-btn"
                                                title="Detail"
                                                onclick="showDetail(this)"
                                                data-id="<?= e($row["id_penyewa"]) ?>"
                                                data-nama="<?= e($row["nama_penyewa"]) ?>"
                                                data-hp="<?= e($row["no_hp"]) ?>"
                                                data-ktp="<?= e($row["no_KTP"]) ?>"
                                                data-sim="<?= e($row["no_simA"]) ?>"
                                                data-tanggal="<?= e($row["tanggal_lahir"]) ?>"
                                                data-alamat="<?= e($row["alamat"]) ?>"
                                            >
                                                <i data-lucide="eye"></i>
                                            </button>


                                            <!-- EDIT -->
                                            <button
                                                type="button"
                                                class="customer-action customer-edit-btn"
                                                title="Edit"
                                                onclick="showEdit(this)"
                                                data-id="<?= e($row["id_penyewa"]) ?>"
                                                data-nama="<?= e($row["nama_penyewa"]) ?>"
                                                data-hp="<?= e($row["no_hp"]) ?>"
                                                data-ktp="<?= e($row["no_KTP"]) ?>"
                                                data-sim="<?= e($row["no_simA"]) ?>"
                                                data-tanggal="<?= e($row["tanggal_lahir"]) ?>"
                                                data-alamat="<?= e($row["alamat"]) ?>"
                                            >
                                                <i data-lucide="pencil"></i>
                                            </button>


                                            <!-- HAPUS -->
                                            <button
                                                type="button"
                                                class="customer-action customer-delete-btn"
                                                title="Hapus"
                                                onclick="showDelete(this)"
                                                data-id="<?= e($row["id_penyewa"]) ?>"
                                                data-nama="<?= e($row["nama_penyewa"]) ?>"
                                            >
                                                <i data-lucide="trash-2"></i>
                                            </button>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="5" class="customer-empty">
                                    Belum ada data pelanggan.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>

                    </table>


                    <p id="noSearchResult" class="customer-empty" style="display:none;">
                        Pelanggan tidak ditemukan.
                    </p>

                </div>

            </div>

        </section>

    </main>

</div>



<!-- ===================================== -->
<!-- MODAL TAMBAH DAN EDIT -->
<!-- ===================================== -->
<div class="customer-modal" id="customerFormModal">

    <div class="customer-modal-card">

        <div class="customer-modal-header">

            <h3 id="customerFormTitle">Tambah Pelanggan</h3>

            <button type="button" class="customer-close" onclick="closeCustomerForm()">
                <i data-lucide="x"></i>
            </button>

        </div>


        <form method="POST" action="pelanggan.php" id="customerForm">

            <input type="hidden" name="id_penyewa" id="formId">

            <div class="customer-form-grid">

                <div class="customer-field full">

                    <label>Nama Lengkap</label>

                    <input
                        type="text"
                        name="nama_penyewa"
                        id="formNama"
                        maxlength="60"
                        placeholder="Masukkan nama lengkap"
                        required
                    >

                </div>


                <div class="customer-field">

                    <label>Nomor HP</label>

                    <input
                        type="tel"
                        name="no_hp"
                        id="formHP"
                        minlength="10"
                        maxlength="15"
                        pattern="[0-9]{10,15}"
                        inputmode="numeric"
                        placeholder="Contoh: 081234567890"
                        title="Nomor HP harus terdiri dari 10 sampai 15 digit angka"
                        required
                    >

                </div>


                <div class="customer-field">

                    <label>Nomor KTP</label>

                    <input
                        type="text"
                        name="no_KTP"
                        id="formKTP"
                        minlength="16"
                        maxlength="16"
                        pattern="[0-9]{16}"
                        inputmode="numeric"
                        placeholder="16 digit nomor KTP"
                        title="Nomor KTP harus terdiri dari 16 digit angka"
                        required
                    >

                </div>


                <div class="customer-field">

                    <label>Nomor SIM A</label>

                    <input
                        type="text"
                        name="no_simA"
                        id="formSIM"
                        minlength="16"
                        maxlength="16"
                        pattern="[0-9]{16}"
                        inputmode="numeric"
                        placeholder="16 digit nomor SIM A"
                        title="Nomor SIM A harus terdiri dari 16 digit angka"
                        required
                    >

                </div>


                <div class="customer-field">

                    <label>Tanggal Lahir</label>

                    <input
                        type="date"
                        name="tanggal_lahir"
                        id="formTanggal"
                        max="<?= date('Y-m-d') ?>"
                        required
                    >

                </div>


                <div class="customer-field full">

                    <label>Alamat</label>

                    <textarea
                        name="alamat"
                        id="formAlamat"
                        maxlength="500"
                        placeholder="Masukkan alamat lengkap"
                        required
                    ></textarea>

                    <small
                        id="alamatError"
                        style="color: #ff9999; font-size: 11px; display: none;"
                    >
                        Alamat harus diisi dengan alamat yang valid.
                    </small>

                </div>

            </div>


            <!-- TOMBOL FORM -->
            <div class="customer-form-actions">

                <button
                    type="button"
                    class="customer-cancel"
                    onclick="closeCustomerForm()"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    name="tambah_pelanggan"
                    id="customerSubmit"
                    class="customer-save"
                >
                    Simpan
                </button>

            </div>

        </form>

    </div>

</div>



<!-- ===================================== -->
<!-- MODAL DETAIL -->
<!-- ===================================== -->
<div class="customer-modal" id="customerDetailModal">

    <div class="customer-modal-card">

        <div class="customer-modal-header">

            <h3>Detail Pelanggan</h3>

            <button type="button" class="customer-close" onclick="closeDetailModal()">
                <i data-lucide="x"></i>
            </button>

        </div>


        <div class="customer-detail-grid">

            <div class="customer-detail-item">
                <small>ID Pelanggan</small>
                <p id="detailId"></p>
            </div>

            <div class="customer-detail-item">
                <small>Nama Lengkap</small>
                <p id="detailNama"></p>
            </div>

            <div class="customer-detail-item">
                <small>Nomor HP</small>
                <p id="detailHP"></p>
            </div>

            <div class="customer-detail-item">
                <small>Nomor KTP</small>
                <p id="detailKTP"></p>
            </div>

            <div class="customer-detail-item">
                <small>Nomor SIM A</small>
                <p id="detailSIM"></p>
            </div>

            <div class="customer-detail-item">
                <small>Tanggal Lahir</small>
                <p id="detailTanggal"></p>
            </div>

            <div class="customer-detail-item" style="grid-column:1/-1;">
                <small>Alamat</small>
                <p id="detailAlamat"></p>
            </div>

        </div>


        <div class="customer-form-actions">

            <button type="button" class="customer-cancel" onclick="closeDetailModal()">
                Tutup
            </button>

        </div>

    </div>

</div>



<!-- ===================================== -->
<!-- MODAL KONFIRMASI HAPUS -->
<!-- ===================================== -->
<div class="customer-modal" id="customerDeleteModal">

    <div class="customer-modal-card customer-delete-card">

        <div class="customer-delete-icon">
            <i data-lucide="trash-2"></i>
        </div>

        <h3>Hapus Data Pelanggan?</h3>

        <p>
            Apakah kamu yakin ingin menghapus data pelanggan
            <span class="customer-delete-name" id="deleteNama"></span>?
        </p>

        <div class="customer-delete-warning">
            Data pelanggan yang berhasil dihapus tidak dapat dikembalikan.
            Jika masih digunakan pada reservasi atau transaksi, data tidak dapat dihapus.
        </div>

        <form method="POST" action="pelanggan.php">

            <input
                type="hidden"
                name="hapus_id_penyewa"
                id="hapusId"
            >

            <div class="customer-delete-actions">

                <button
                    type="button"
                    class="customer-cancel"
                    onclick="closeDeleteModal()"
                >
                    Batal
                </button>

                <button
                    type="submit"
                    name="hapus_pelanggan"
                    class="customer-delete-confirm"
                >
                    Ya, Hapus
                </button>

            </div>

        </form>

    </div>

</div>



<script>

// =====================================
// ELEMEN VALIDASI ALAMAT
// =====================================
const inputAlamat = document.getElementById("formAlamat");
const alamatError = document.getElementById("alamatError");


// =====================================
// MODAL TAMBAH
// =====================================
function openAddModal() {

    document.getElementById("customerForm").reset();

    document.getElementById("customerForm").action = "pelanggan.php";
    document.getElementById("customerFormTitle").textContent = "Tambah Pelanggan";

    document.getElementById("formId").value = "";

    document.getElementById("customerSubmit").name = "tambah_pelanggan";
    document.getElementById("customerSubmit").textContent = "Simpan";

    inputAlamat.setCustomValidity("");
    alamatError.style.display = "none";

    openCustomerForm();
}


// =====================================
// MODAL EDIT
// =====================================
function showEdit(button) {

    const data = button.dataset;

    document.getElementById("customerForm").reset();

    document.getElementById("customerForm").action = "pelanggan.php";
    document.getElementById("customerFormTitle").textContent = "Edit Pelanggan";

    document.getElementById("formId").value = data.id;
    document.getElementById("formNama").value = data.nama;
    document.getElementById("formHP").value = data.hp;
    document.getElementById("formKTP").value = data.ktp;
    document.getElementById("formSIM").value = data.sim;
    document.getElementById("formTanggal").value = data.tanggal;
    document.getElementById("formAlamat").value = data.alamat;

    document.getElementById("customerSubmit").name = "edit_pelanggan";
    document.getElementById("customerSubmit").textContent = "Simpan Perubahan";

    inputAlamat.setCustomValidity("");
    alamatError.style.display = "none";

    openCustomerForm();
}


// =====================================
// BUKA FORM
// =====================================
function openCustomerForm() {

    document.getElementById("customerFormModal").classList.add("show");
    document.body.style.overflow = "hidden";

}


// =====================================
// TUTUP FORM
// =====================================
function closeCustomerForm() {

    document.getElementById("customerFormModal").classList.remove("show");
    document.body.style.overflow = "";

}


// =====================================
// MODAL DETAIL
// =====================================
function showDetail(button) {

    const data = button.dataset;

    document.getElementById("detailId").textContent = data.id;
    document.getElementById("detailNama").textContent = data.nama;
    document.getElementById("detailHP").textContent = data.hp;
    document.getElementById("detailKTP").textContent = data.ktp;
    document.getElementById("detailSIM").textContent = data.sim;

    document.getElementById("detailTanggal").textContent =
        data.tanggal ? data.tanggal : "-";

    document.getElementById("detailAlamat").textContent =
        data.alamat ? data.alamat : "-";

    document.getElementById("customerDetailModal").classList.add("show");
    document.body.style.overflow = "hidden";

}


// =====================================
// TUTUP DETAIL
// =====================================
function closeDetailModal() {

    document.getElementById("customerDetailModal").classList.remove("show");
    document.body.style.overflow = "";

}


// =====================================
// MODAL HAPUS
// =====================================
function showDelete(button) {

    const data = button.dataset;

    document.getElementById("hapusId").value = data.id;
    document.getElementById("deleteNama").textContent = data.nama;

    document.getElementById("customerDeleteModal").classList.add("show");
    document.body.style.overflow = "hidden";

}


// =====================================
// TUTUP MODAL HAPUS
// =====================================
function closeDeleteModal() {

    document.getElementById("customerDeleteModal").classList.remove("show");
    document.body.style.overflow = "";

}


// =====================================
// KLIK BAGIAN LUAR MODAL
// =====================================
document.querySelectorAll(".customer-modal").forEach(function(modal) {

    modal.addEventListener("click", function(event) {

        if (event.target === modal) {

            modal.classList.remove("show");
            document.body.style.overflow = "";

        }

    });

});


// =====================================
// PENCARIAN PELANGGAN
// =====================================
document.getElementById("searchPelanggan").addEventListener("input", function() {

    const keyword = this.value.toLowerCase();
    const rows = document.querySelectorAll("#tablePelanggan tbody tr");

    let ditemukan = 0;

    rows.forEach(function(row) {

        const isi = row.textContent.toLowerCase();

        if (isi.includes(keyword)) {

            row.style.display = "";
            ditemukan++;

        } else {

            row.style.display = "none";

        }

    });

    document.getElementById("noSearchResult").style.display =
        ditemukan === 0 ? "block" : "none";

});


// =====================================
// NOTIFIKASI HILANG OTOMATIS
// =====================================
document.querySelectorAll(".customer-alert").forEach(function(alert) {

    setTimeout(function() {

        alert.style.transition = "opacity 0.3s ease, transform 0.3s ease";
        alert.style.opacity = "0";
        alert.style.transform = "translateY(-8px)";

        setTimeout(function() {
            alert.remove();
        }, 300);

    }, 3000);

});


// =====================================
// VALIDASI ALAMAT
// =====================================
function validasiAlamat() {

    const alamat = inputAlamat.value.trim();

    if (!/[a-zA-Z]/.test(alamat)) {

        alamatError.style.display = "block";

        inputAlamat.setCustomValidity(
            "Alamat harus diisi dengan alamat yang valid."
        );

        return false;

    } else {

        alamatError.style.display = "none";

        inputAlamat.setCustomValidity("");

        return true;

    }

}

inputAlamat.addEventListener("input", validasiAlamat);

document.getElementById("customerForm").addEventListener("submit", function(event) {

    if (!validasiAlamat()) {
        event.preventDefault();
        inputAlamat.reportValidity();
    }

});

function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("show");
}
// =====================================
// ICON
// =====================================
lucide.createIcons();

</script>

</body>
</html>
