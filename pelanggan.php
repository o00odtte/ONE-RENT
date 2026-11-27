<?php
// MODUL DATA PELANGGAN: validasi identitas pelanggan serta proses tambah, edit, hapus, dan tampilan data.

require_once 'auth.php';
require_once 'koneksi.php';

date_default_timezone_set('Asia/Jakarta');

// Mengamankan output HTML
function e($data) {
    return htmlspecialchars($data ?? '', ENT_QUOTES, 'UTF-8');
}

// Format dan validasi nomor HP
function formatHP($nomor) {
    $nomor = preg_replace('/\s+/', '', trim($nomor));
    return preg_match('/^[0-9]{10,15}$/', $nomor) ? $nomor : false;
}

// Format dan validasi nomor KTP
function formatKTP($nomor) {
    $nomor = preg_replace('/\s+/', '', trim($nomor));
    return preg_match('/^[0-9]{16}$/', $nomor) ? $nomor : false;
}

// Format dan validasi nomor SIM A
function formatSIM($nomor) {
    $nomor = preg_replace('/\s+/', '', trim($nomor));
    return preg_match('/^[0-9]{16}$/', $nomor) ? $nomor : false;
}

// Validasi data pelanggan
function validasiPelanggan($nama, $hp, $ktp, $sim, $tanggal, $alamat) {
    if (
        $nama === '' || strlen($nama) > 60 ||
        !$hp || !$ktp || !$sim || $tanggal === '' || $alamat === ''
    ) {
        return 'Periksa kembali data pelanggan. Pastikan semua data terisi dengan benar.';
    }

    if (strlen($alamat) > 500 || !preg_match('/[a-zA-Z]/', $alamat)) {
        return 'Alamat tidak valid atau terlalu panjang.';
    }

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        return 'Format tanggal lahir tidak valid.';
    }

    $bagianTanggal = explode('-', $tanggal);

    if (!checkdate((int)$bagianTanggal[1], (int)$bagianTanggal[2], (int)$bagianTanggal[0])) {
        return 'Tanggal lahir tidak valid.';
    }

    if ($tanggal > date('Y-m-d')) {
        return 'Tanggal lahir tidak boleh melebihi hari ini.';
    }

    return '';
}

$pesan = '';
$error = '';

// Proses tambah pelanggan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_pelanggan'])) {
    $nama = trim($_POST['nama_penyewa'] ?? '');
    $hp = formatHP($_POST['no_hp'] ?? '');
    $ktp = formatKTP($_POST['no_KTP'] ?? '');
    $sim = formatSIM($_POST['no_simA'] ?? '');
    $tanggal = trim($_POST['tanggal_lahir'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    $error = validasiPelanggan($nama, $hp, $ktp, $sim, $tanggal, $alamat);

    if ($error === '') {
        // Cek nomor KTP atau SIM A yang sudah terdaftar
        $cek = $conn->prepare(
            "SELECT id_penyewa FROM penyewa WHERE no_KTP = ? OR no_simA = ?"
        );
        $cek->bind_param('ss', $ktp, $sim);
        $cek->execute();
        $hasilCek = $cek->get_result();

        if ($hasilCek->num_rows > 0) {
            $error = 'Nomor KTP atau SIM A sudah terdaftar.';
        } else {
            // Membuat ID pelanggan otomatis
            $hasilID = $conn->query("
                SELECT MAX(CAST(SUBSTRING(id_penyewa, 4) AS UNSIGNED)) AS nomor
                FROM penyewa
                WHERE id_penyewa REGEXP '^PNY[0-9]+$'
            ");

            $dataID = $hasilID->fetch_assoc();
            $nomorBaru = (int)($dataID['nomor'] ?? 0) + 1;
            $id = 'PNY' . str_pad($nomorBaru, 3, '0', STR_PAD_LEFT);

            // Menyimpan pelanggan
            $stmt = $conn->prepare("
                INSERT INTO penyewa
                (id_penyewa, nama_penyewa, no_hp, no_KTP, no_simA, tanggal_lahir, alamat)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                'sssssss',
                $id, $nama, $hp, $ktp, $sim, $tanggal, $alamat
            );

            if ($stmt->execute()) {
                header('Location: pelanggan.php?berhasil=1');
                exit;
            }

            $error = 'Gagal menambahkan pelanggan.';
        }

        $cek->close();
    }
}

// Proses edit pelanggan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_pelanggan'])) {
    $id = trim($_POST['id_penyewa'] ?? '');
    $nama = trim($_POST['nama_penyewa'] ?? '');
    $hp = formatHP($_POST['no_hp'] ?? '');
    $ktp = formatKTP($_POST['no_KTP'] ?? '');
    $sim = formatSIM($_POST['no_simA'] ?? '');
    $tanggal = trim($_POST['tanggal_lahir'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    $error = validasiPelanggan($nama, $hp, $ktp, $sim, $tanggal, $alamat);

    if ($error === '' && $id === '') {
        $error = 'Data pelanggan tidak ditemukan.';
    }

    if ($error === '') {
        // Pastikan pelanggan masih tersedia
        $cekID = $conn->prepare("SELECT id_penyewa FROM penyewa WHERE id_penyewa = ?");
        $cekID->bind_param('s', $id);
        $cekID->execute();

        if ($cekID->get_result()->num_rows === 0) {
            $error = 'Data pelanggan tidak ditemukan.';
        }
        $cekID->close();
    }

    if ($error === '') {
        // Cek KTP atau SIM A milik pelanggan lain
        $cek = $conn->prepare("
            SELECT id_penyewa FROM penyewa
            WHERE (no_KTP = ? OR no_simA = ?) AND id_penyewa != ?
        ");
        $cek->bind_param('sss', $ktp, $sim, $id);
        $cek->execute();

        if ($cek->get_result()->num_rows > 0) {
            $error = 'Nomor KTP atau SIM A sudah digunakan pelanggan lain.';
        } else {
            // Memperbarui data pelanggan
            $stmt = $conn->prepare("
                UPDATE penyewa
                SET nama_penyewa = ?, no_hp = ?, no_KTP = ?,
                    no_simA = ?, tanggal_lahir = ?, alamat = ?
                WHERE id_penyewa = ?
            ");

            $stmt->bind_param(
                'sssssss',
                $nama, $hp, $ktp, $sim, $tanggal, $alamat, $id
            );

            if ($stmt->execute()) {
                header('Location: pelanggan.php?diedit=1');
                exit;
            }

            $error = 'Gagal memperbarui data pelanggan.';
        }

        $cek->close();
    }
}

// Proses hapus pelanggan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_pelanggan'])) {
    $id = trim($_POST['id_penyewa'] ?? '');

    $stmt = $conn->prepare("DELETE FROM penyewa WHERE id_penyewa = ?");
    $stmt->bind_param('s', $id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            header('Location: pelanggan.php?dihapus=1');
            exit;
        }

        $error = 'Data pelanggan tidak ditemukan.';
    } else {
        if ($stmt->errno === 1451) {
            $error = 'Pelanggan tidak dapat dihapus karena masih digunakan pada data reservasi atau transaksi.';
        } else {
            $error = 'Gagal menghapus data pelanggan.';
        }
    }
}

// Pesan notifikasi
if (isset($_GET['berhasil'])) {
    $pesan = 'Pelanggan berhasil ditambahkan.';
} elseif (isset($_GET['diedit'])) {
    $pesan = 'Data pelanggan berhasil diperbarui.';
} elseif (isset($_GET['dihapus'])) {
    $pesan = 'Data pelanggan berhasil dihapus.';
}

// Mengambil seluruh data pelanggan
$queryPelanggan = $conn->query("SELECT * FROM penyewa ORDER BY id_penyewa ASC");
$totalPelanggan = $queryPelanggan->num_rows;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Pelanggan | ONE RENT</title>

    <link rel="stylesheet" href="style.css">

    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/lucide@latest"></script>

    
<style>
body {
    font-family: 'Outfit', sans-serif;
}

/* JUDUL HALAMAN */
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

/* TOTAL PELANGGAN */
.customer-summary {
    display: flex;
    align-items: center;
    gap: 16px;
    max-width: 300px;
    padding: 20px;
    margin-bottom: 22px;
    background: var(--card);
    border-radius: 14px;
}

.customer-summary-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 48px;
    height: 48px;
    color: var(--peach);
    background: rgba(249,177,122,.15);
    border-radius: 12px;
}

.customer-summary small {
    color: #c0c2d0;
    font-size: 12px;
}

.customer-summary h3 {
    margin-top: 4px;
    color: white;
    font-size: 23px;
}

/* PENCARIAN DAN TOMBOL TAMBAH */
.customer-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 20px;
}

.customer-search {
    position: relative;
    flex: 1;
    max-width: 350px;
}

.customer-search i,
.customer-search svg {
    position: absolute;
    top: 50%;
    left: 14px;
    width: 17px;
    height: 17px;
    transform: translateY(-50%);
    color: #999fb9;
}

.customer-search input {
    width: 100%;
    padding: 12px 14px 12px 42px;
    color: white;
    background: #2D3250;
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 9px;
    outline: none;
    font-family: inherit;
}

.customer-search input:focus {
    border-color: var(--peach);
}

.customer-add-btn {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 17px;
    color: #2D3250;
    background: var(--peach);
    border: none;
    border-radius: 9px;
    font-family: inherit;
    font-weight: 800;
    cursor: pointer;
}

.customer-add-btn:hover {
    opacity: .9;
}

/* TABEL PELANGGAN */
.customer-table-wrap {
    overflow-x: auto;
}

.customer-table {
    width: 100%;
    min-width: 750px;
    border-collapse: collapse;
}

.customer-table th {
    padding: 15px 13px;
    color: #aeb2c8;
    background: rgba(45,50,80,.6);
    font-size: 12px;
    font-weight: 700;
    text-align: left;
}

.customer-table td {
    padding: 16px 13px;
    color: #f2f2f7;
    border-bottom: 1px solid rgba(255,255,255,.07);
    font-size: 13px;
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
    display: flex;
    align-items: center;
    justify-content: center;
    width: 33px;
    height: 33px;
    background: transparent;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}

.customer-action i,
.customer-action svg {
    width: 16px;
    height: 16px;
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
    padding: 35px !important;
    color: #aeb2c8 !important;
    text-align: center;
}

/* MODAL */
.customer-modal {
    position: fixed;
    inset: 0;
    z-index: 999;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(10,12,25,.72);
    backdrop-filter: blur(5px);
}

.customer-modal.show {
    display: flex;
}

.customer-modal-card {
    width: 100%;
    max-width: 650px;
    max-height: 90vh;
    overflow-y: auto;
    padding: 26px;
    background: #424769;
    border-radius: 16px;
    box-shadow: 0 20px 60px rgba(0,0,0,.35);
}

.customer-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 23px;
}

.customer-modal-header h3 {
    color: white;
    font-size: 19px;
}

.customer-close {
    color: #c6c8d7;
    background: transparent;
    border: none;
    cursor: pointer;
}

/* FORM PELANGGAN */
.customer-form-grid,
.customer-detail-grid {
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
    color: white;
    background: #2D3250;
    border: 1px solid rgba(255,255,255,.12);
    border-radius: 8px;
    outline: none;
    font-family: inherit;
    font-size: 13px;
}

.customer-field input:focus,
.customer-field textarea:focus {
    border-color: var(--peach);
}

.customer-field textarea {
    min-height: 75px;
    resize: vertical;
}

.customer-field input::placeholder,
.customer-field textarea::placeholder {
    color: #9298b1;
}

/* TOMBOL FORM */
.customer-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 24px;
}

.customer-cancel,
.customer-save {
    padding: 11px 18px;
    border: none;
    border-radius: 8px;
    font-family: inherit;
    font-weight: 700;
    cursor: pointer;
}

.customer-cancel {
    color: white;
    background: #555b7d;
}

.customer-save {
    color: #2D3250;
    background: var(--peach);
}

/* DETAIL PELANGGAN */
.customer-detail-grid {
    gap: 18px;
}

.customer-detail-item {
    min-width: 0;
    padding: 14px;
    background: #2D3250;
    border-radius: 9px;
}

.customer-detail-item small {
    display: block;
    margin-bottom: 7px;
    color: #aeb2c8;
    font-size: 11px;
}

.customer-detail-item p {
    color: white;
    font-size: 13px;
    overflow-wrap: anywhere;
}

/* NOTIFIKASI */
.customer-alert {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 13px 16px;
    margin-bottom: 18px;
    border-radius: 9px;
    font-size: 13px;
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
    color: inherit;
    background: transparent;
    border: none;
    cursor: pointer;
}

/* MODAL HAPUS */
.customer-delete-card {
    max-width: 430px;
    text-align: center;
}

.customer-delete-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 65px;
    height: 65px;
    margin: 0 auto 18px;
    color: #e47777;
    background: rgba(217,83,79,.15);
    border-radius: 50%;
}

.customer-delete-icon i,
.customer-delete-icon svg {
    width: 30px;
    height: 30px;
}

.customer-delete-card h3 {
    margin-bottom: 10px;
    color: white;
    font-size: 19px;
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
    padding: 11px;
    margin-top: 15px;
    color: #ffb1b1;
    background: rgba(217,83,79,.12);
    border: 1px solid rgba(217,83,79,.2);
    border-radius: 9px;
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
    padding: 11px 18px;
    color: white;
    background: #D9534F;
    border: none;
    border-radius: 8px;
    font-family: inherit;
    font-weight: 700;
    cursor: pointer;
}

.customer-delete-confirm:hover {
    background: #c9433f;
}

/* TAMPILAN HP */
@media (max-width: 650px) {
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
                name="id_penyewa"
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
<script src="script.js"></script>
</body>
</html>
