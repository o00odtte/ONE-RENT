<?php
require_once 'auth.php';
require_once 'koneksi.php';
date_default_timezone_set('Asia/Jakarta');

// Fungsi untuk mengamankan output HTML
function e($data) {
    return htmlspecialchars($data ?? "", ENT_QUOTES, "UTF-8");
}

// Fungsi validasi dan format nomor polisi
function formatPlat($plat) {
    $plat = strtoupper(preg_replace('/\s+/', '', trim($plat)));

    if (!preg_match('/^([A-Z]{1,2})(\d{1,4})([A-Z]{1,3})$/', $plat, $hasil)) {
        return false;
    }

    return $hasil[1] . " " . $hasil[2] . " " . $hasil[3];
}

// Fungsi validasi kapasitas kursi
function formatSeat($seat) {
    $seat = trim($seat);

    if (!preg_match('/^[1-9][0-9]? Seat$/i', $seat)) {
        return false;
    }

    return preg_replace('/^([0-9]+)\s+seat$/i', '$1 Seat', $seat);
}

$pesan = "";
$error = "";


// PROSES TAMBAH MOBIL
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["tambah_mobil"])) {

    $merk = trim($_POST["merk_mobil"] ?? '');
    $plat = formatPlat($_POST["plat_kendaraan"] ?? '');
    $seat = formatSeat($_POST["seat_row"] ?? '');
    $tarifRaw = trim((string)($_POST["tarif_sewa"] ?? ''));
    $tarif = is_numeric($tarifRaw) ? (float)$tarifRaw : 0;
    $status = $_POST["status_mobil"] ?? '';
    if (!in_array($status, ["Tersedia", "Perawatan"], true)) {
        $status = false;
    }
    if ($merk === "" || mb_strlen($merk) > 30 || $plat === false || strlen($plat) > 10 || $seat === false || $tarifRaw === '' || !is_finite($tarif) || $tarif <= 0 || $tarif > 99999999.99 || $status === false) {

        $error = "Data belum lengkap atau format input tidak valid.";

    } else {

        // Cek nomor polisi yang sudah terdaftar
        $cekPlat = mysqli_prepare($conn, "
            SELECT id_mobil FROM mobil
            WHERE plat_kendaraan = ?
        ");

        mysqli_stmt_bind_param($cekPlat, "s", $plat);
        mysqli_stmt_execute($cekPlat);
        $hasilCek = mysqli_stmt_get_result($cekPlat);

        if (mysqli_num_rows($hasilCek) > 0) {

            $error = "Nomor polisi sudah terdaftar! Gunakan nomor lain.";

        } else {

            // Membuat ID otomatis MBL001, MBL002, dan seterusnya
            $queryID = mysqli_query($conn, "
                SELECT MAX(CAST(SUBSTRING(id_mobil, 4) AS UNSIGNED)) AS nomor
                FROM mobil
                WHERE id_mobil REGEXP '^MBL[0-9]+$'
            ");

            $hasilID = mysqli_fetch_assoc($queryID);
            $nomorBaru = (int) ($hasilID["nomor"] ?? 0) + 1;
            $idMobil = "MBL" . str_pad($nomorBaru, 3, "0", STR_PAD_LEFT);

            $stmt = mysqli_prepare($conn, "
                INSERT INTO mobil
                (id_mobil, plat_kendaraan, merk_mobil, seat_row, status_mobil, tarif_sewa)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "sssssd",
                $idMobil,
                $plat,
                $merk,
                $seat,
                $status,
                $tarif
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: mobil.php?berhasil=1");
                exit;
            } else {
                $error = "Gagal menambahkan data mobil.";
            }
        }
    }
}

// PROSES HAPUS MOBIL
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["hapus_mobil"])) {

    $idMobil = trim($_POST["id_mobil"]);

    if ($idMobil === "") {
        $error = "ID mobil tidak valid.";
    } else {
        $cekMobil = mysqli_prepare($conn, "SELECT id_mobil FROM mobil WHERE id_mobil = ?");
        mysqli_stmt_bind_param($cekMobil, "s", $idMobil);
        mysqli_stmt_execute($cekMobil);
        $adaMobil = mysqli_stmt_get_result($cekMobil);
        $mobilDitemukan = mysqli_num_rows($adaMobil) > 0;
        mysqli_stmt_close($cekMobil);
        if (!$mobilDitemukan) { $error = "Data mobil tidak ditemukan."; }
        else {
        $stmt = mysqli_prepare($conn, "
            DELETE FROM mobil
            WHERE id_mobil = ?
        ");

        mysqli_stmt_bind_param($stmt, "s", $idMobil);

        if (mysqli_stmt_execute($stmt)) {
            header("Location: mobil.php?dihapus=1");
            exit;
        } else {
            $error = "Mobil tidak dapat dihapus. Mungkin data mobil masih digunakan pada transaksi atau reservasi.";
        }

        mysqli_stmt_close($stmt);
        }
    }
}

// PROSES EDIT MOBIL
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["edit_mobil"])) {

    $idMobil = trim($_POST["id_mobil"] ?? '');
    $merk = trim($_POST["merk_mobil"] ?? '');
    $plat = formatPlat($_POST["plat_kendaraan"] ?? '');
    $seat = formatSeat($_POST["seat_row"] ?? '');
    $tarifRaw = trim((string)($_POST["tarif_sewa"] ?? ''));
    $tarif = is_numeric($tarifRaw) ? (float)$tarifRaw : 0;
    $status = $_POST["status_mobil"] ?? '';

if (!in_array($status, ["Tersedia", "Perawatan"], true)) {
    $status = false;
}

if ($idMobil === '' || $merk === "" || mb_strlen($merk) > 30 || $plat === false || strlen($plat) > 10 || $seat === false || $tarifRaw === '' || !is_finite($tarif) || $tarif <= 0 || $tarif > 99999999.99 || $status === false) {
    $error = "Data belum lengkap atau format input tidak valid.";
} else {

        $cekIdMobil = mysqli_prepare($conn, "SELECT id_mobil FROM mobil WHERE id_mobil=?");
        mysqli_stmt_bind_param($cekIdMobil, "s", $idMobil);
        mysqli_stmt_execute($cekIdMobil);
        $idMobilResult = mysqli_stmt_get_result($cekIdMobil);
        $idMobilAda = mysqli_num_rows($idMobilResult) > 0;
        mysqli_stmt_close($cekIdMobil);
        if (!$idMobilAda) { $error = "Data mobil tidak ditemukan."; }
        else {
        // Status mobil yang masih memiliki transaksi aktif tidak boleh diubah manual.
        $aktif = mysqli_prepare($conn, "SELECT t.id_transaksi FROM transaksisewa t JOIN reservasi r ON t.id_reservasi=r.id_reservasi LEFT JOIN pengembalian k ON k.id_transaksi=t.id_transaksi WHERE r.id_mobil=? AND k.id_pengembalian IS NULL LIMIT 1");
        mysqli_stmt_bind_param($aktif, "s", $idMobil);
        mysqli_stmt_execute($aktif);
        $aktifResult = mysqli_stmt_get_result($aktif);
        $sedangDisewa = mysqli_num_rows($aktifResult) > 0;
        mysqli_stmt_close($aktif);
        if ($sedangDisewa) {
            $status = 'Disewa';
        }

        // Cek nomor polisi milik mobil lain
        $cekPlat = mysqli_prepare($conn, "
            SELECT id_mobil FROM mobil
            WHERE plat_kendaraan = ?
            AND id_mobil != ?
        ");

        mysqli_stmt_bind_param($cekPlat, "ss", $plat, $idMobil);
        mysqli_stmt_execute($cekPlat);
        $hasilCek = mysqli_stmt_get_result($cekPlat);

        if (mysqli_num_rows($hasilCek) > 0) {

            $error = "Nomor polisi sudah digunakan oleh mobil lain!";

        } else {

            $stmt = mysqli_prepare($conn, "
                UPDATE mobil
                SET plat_kendaraan = ?,
                    merk_mobil = ?,
                    seat_row = ?,
                    status_mobil = ?,
                    tarif_sewa = ?
                WHERE id_mobil = ?
            ");

            mysqli_stmt_bind_param(
                $stmt,
                "ssssds",
                $plat,
                $merk,
                $seat,
                $status,
                $tarif,
                $idMobil
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: mobil.php?diedit=1");
                exit;
            } else {
                $error = "Gagal mengubah data mobil.";
            }
        }
        }
    }
}


// PESAN BERHASIL
if (isset($_GET["berhasil"])) {
    $pesan = "Data mobil berhasil ditambahkan!";
}

if (isset($_GET["diedit"])) {
    $pesan = "Data mobil berhasil diperbarui!";
}

if (isset($_GET["dihapus"])) {
    $pesan = "Data mobil berhasil dihapus!";
}


// AMBIL DATA MOBIL DARI DATABASE
$result = mysqli_query($conn, "SELECT * FROM mobil ORDER BY id_mobil ASC");

if (!$result) {
    die("Gagal mengambil data mobil: " . mysqli_error($conn));
}

$totalMobil = mysqli_num_rows($result);
$dataMobil = [];
$totalTersedia = 0;
$totalDisewa = 0;

while ($row = mysqli_fetch_assoc($result)) {

    $dataMobil[] = $row;

    $status = strtolower(trim($row["status_mobil"]));

    if ($status === "tersedia") {
        $totalTersedia++;
    } elseif ($status === "disewa") {
        $totalDisewa++;
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Mobil | ONE RENT</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="style.css">
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
            <a href="dashboard.php" class="menu-item"><i data-lucide="layout-dashboard"></i>Dashboard</a>
            <a href="mobil.php" class="menu-item active"><i data-lucide="car-front"></i>Data Mobil</a>
            <a href="pelanggan.php" class="menu-item"><i data-lucide="users"></i>Data Pelanggan</a>
            <a href="reservasi.php" class="menu-item"><i data-lucide="calendar-check"></i>Reservasi</a>
            <a href="transaksi.php" class="menu-item"><i data-lucide="clipboard-list"></i>Transaksi Sewa</a>
            <a href="pembayaran.php" class="menu-item"><i data-lucide="wallet"></i>Pembayaran</a>
            <a href="pengembalian.php" class="menu-item"><i data-lucide="undo-2"></i>Pengembalian</a>
            <a href="denda.php" class="menu-item"><i data-lucide="receipt"></i>Denda</a>
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
                <h2>Data Mobil</h2>
                <p>Kelola informasi kendaraan ONE RENT.</p>
            </div>

            <div class="topbar-right">
                <span class="date" id="currentDate"></span>
                <div class="avatar"><?= e(strtoupper(substr($_SESSION["nama_admin"] ?? "A", 0, 1))) ?></div>
            </div>
        </header>

        <section class="content">

            <div class="data-intro">
                <div>
                    <h2>Daftar Kendaraan</h2>
                    <p>Informasi mobil yang terdaftar dalam sistem.</p>
                </div>

                <button class="add-car-btn" id="openForm" type="button">
                    <i data-lucide="plus"></i>
                    Tambah Mobil
                </button>
            </div>

            
            <!-- NOTIFIKASI -->
            <?php if ($pesan !== ""): ?>
                <div class="alert-message alert-success">
                    <div class="alert-icon">
                        <i data-lucide="check-circle"></i>
                    </div>

                    <div class="alert-content">
                        <strong>Berhasil!</strong>
                        <p><?= e($pesan) ?></p>
                    </div>

                    <button type="button" class="alert-close" onclick="closeAlert(this)">
                        <i data-lucide="x"></i>
                    </button>
                </div>
            <?php endif; ?>

            <?php if ($error !== ""): ?>
                <div class="alert-message alert-error">
                    <div class="alert-icon">
                        <i data-lucide="alert-circle"></i>
                    </div>

                    <div class="alert-content">
                        <strong>Terjadi Kesalahan!</strong>
                        <p><?= e($error) ?></p>
                    </div>

                    <button type="button" class="alert-close" onclick="closeAlert(this)">
                        <i data-lucide="x"></i>
                    </button>
                </div>
            <?php endif; ?>

            <!-- SUMMARY -->
            <div class="car-summary">

                <div class="car-summary-card">
                    <span>Total Mobil</span>
                    <strong><?= $totalMobil ?></strong>
                </div>

                <div class="car-summary-card">
                    <span>Tersedia</span>
                    <strong><?= $totalTersedia ?></strong>
                </div>

                <div class="car-summary-card">
                    <span>Disewa</span>
                    <strong><?= $totalDisewa ?></strong>
                </div>

            </div>

            <!-- TABEL MOBIL -->
            <div class="panel car-table-panel">

                <div class="car-toolbar">
                    <div>
                        <h3>Data Kendaraan</h3>
                        <p>Daftar seluruh mobil yang terdaftar.</p>
                    </div>

                    <div class="car-filters">
                        <div class="car-search">
                            <i data-lucide="search"></i>
                            <input type="text" id="searchCar"
                                   placeholder="Cari nama atau plat nomor...">
                        </div>

                        <select id="filterStatus">
                            <option value="">Semua Status</option>
                            <option value="Tersedia">Tersedia</option>
                            <option value="Disewa">Disewa</option>
                            <option value="Perawatan">Perawatan</option>
                        </select>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table class="car-table">
                        <thead>
                            <tr>
                                <th>Mobil</th>
                                <th>Nomor Polisi</th>
                                <th>Kapasitas/Kursi</th>
                                <th>Harga / Hari</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>

                        <tbody id="carTableBody">

                        <?php if ($totalMobil > 0): ?>

                            <?php foreach ($dataMobil as $mobil): ?>

                                <?php
                                $status = trim($mobil["status_mobil"]);

                                if (strtolower($status) === "tersedia") {
                                    $statusClass = "available-status";
                                } elseif (strtolower($status) === "disewa") {
                                    $statusClass = "rented-status";
                                } else {
                                    $statusClass = "maintenance-status";
                                }
                                ?>

                                <tr data-status="<?= e($status) ?>">
                                    <td>
                                        <div class="car-name">
                                            <div class="car-icon">
                                                <i data-lucide="car-front"></i>
                                            </div>
                                            <strong><?= e($mobil["merk_mobil"]) ?></strong>
                                        </div>
                                    </td>

                                    <td><?= e($mobil["plat_kendaraan"]) ?></td>

                                    <td><?= e($mobil["seat_row"]) ?></td>

                                    <td>
                                        Rp<?= number_format((float)$mobil["tarif_sewa"], 0, ",", ".") ?>
                                    </td>

                                    <td>
                                        <span class="car-status <?= $statusClass ?>">
                                            <?= e($status) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <div class="action-buttons">                                        
                                        <button class="action-btn detail-btn" type="button"
                                                title="Detail"
                                                data-id="<?= e($mobil["id_mobil"]) ?>"
                                                data-merk="<?= e($mobil["merk_mobil"]) ?>"
                                                data-plat="<?= e($mobil["plat_kendaraan"]) ?>"
                                                data-seat="<?= e($mobil["seat_row"]) ?>"
                                                data-tarif="<?= e($mobil["tarif_sewa"]) ?>"
                                                data-status="<?= e($mobil["status_mobil"]) ?>">
                                                <i data-lucide="eye"></i>
                                        </button>                                           
                                        <button class="action-btn edit-btn" type="button"
                                            title="Edit"
                                            data-id="<?= e($mobil["id_mobil"]) ?>"
                                            data-merk="<?= e($mobil["merk_mobil"]) ?>"
                                            data-plat="<?= e($mobil["plat_kendaraan"]) ?>"
                                            data-seat="<?= e($mobil["seat_row"]) ?>"
                                            data-tarif="<?= e($mobil["tarif_sewa"]) ?>"
                                            data-status="<?= e($mobil["status_mobil"]) ?>">
                                            <i data-lucide="pencil"></i>
                                        </button>            
                                        <button class="action-btn delete-btn" type="button"
                                            title="Hapus"
                                            data-id="<?= e($mobil["id_mobil"]) ?>"
                                            data-merk="<?= e($mobil["merk_mobil"]) ?>"
                                            data-plat="<?= e($mobil["plat_kendaraan"]) ?>">
                                            <i data-lucide="trash-2"></i>
                                        </button>
                                        </div>
                                    </td>
                                </tr>

                            <?php endforeach; ?>

                        <?php else: ?>

                            <tr>
                                <td colspan="6" style="text-align:center; padding:30px;">
                                    Belum ada data mobil.
                                </td>
                            </tr>

                        <?php endif; ?>

                        </tbody>
                    </table>
                </div>

                <p class="table-note" id="tableNote">
                    Menampilkan <?= $totalMobil ?> kendaraan.
                </p>

            </div>

            <!-- FORM TAMBAH MOBIL -->
            <div class="car-form-panel" id="carFormPanel">

                <div class="car-form-header">
                    <div>
                        <h3>Tambah Mobil</h3>
                        <p>Masukkan informasi kendaraan baru.</p>
                    </div>

                    <button class="action-btn" id="closeForm" type="button">
                        <i data-lucide="x"></i>
                    </button>
                </div>

                <form method="POST" action="mobil.php">

                    <div class="car-form-grid">

                        <label>
                            Merek Mobil
                            <input type="text" name="merk_mobil" maxlength="30"
                                   placeholder="Contoh: Toyota Avanza" required>
                        </label>

                        <label>
                            Nomor Polisi
                            <input type="text" name="plat_kendaraan"
                                   placeholder="Contoh: KB 1234 AB" maxlength="12" required>
                        </label>

                        <label>
                            Kapasitas/Kursi
                            <input type="text" name="seat_row"
                                   placeholder="Contoh: 7 Seat" maxlength="10" required>
                        </label>

                        <label>
                            Harga Sewa per Hari
                            <input type="number" name="tarif_sewa"
                                   placeholder="Contoh: 350000" min="1" max="99999999.99" step="0.01" required>
                        </label>

                        <label>
                            Status
                            <select name="status_mobil" required>
                                <option value="Tersedia">Tersedia</option>
                                <option value="Perawatan">Perawatan</option>
                            </select>
                        </label>

                    </div>

                    <div class="car-form-actions">
                        <button type="button" class="cancel-btn" id="cancelForm">
                            Batal
                        </button>

                        <button type="submit" name="tambah_mobil" value="1"
                                class="add-car-btn">
                            Simpan Mobil
                        </button>
                    </div>

                </form>
            </div>


<!-- POPUP EDIT MOBIL -->
<div class="car-form-panel" id="editFormPanel">

    <div class="car-form-header">
        <div>
            <h3>Edit Data Mobil</h3>
            <p>Perbarui informasi kendaraan.</p>
        </div>

        <button class="action-btn" id="closeEditForm" type="button">
            <i data-lucide="x"></i>
        </button>
    </div>

    <form method="POST" action="mobil.php">

        <input type="hidden" name="id_mobil" id="editId">

        <div class="car-form-grid">

            <label>
                Merek Mobil
                <input type="text" name="merk_mobil" id="editMerk" maxlength="30" required>
            </label>

            <label>
                Nomor Polisi
                <input type="text" name="plat_kendaraan" id="editPlat"
                       maxlength="12" required>
            </label>

            <label>
                Kapasitas/Kursi
                <input type="text" name="seat_row" id="editSeat"
                       maxlength="10" required>
            </label>

            <label>
                Harga Sewa per Hari
                <input type="number" name="tarif_sewa" id="editTarif"
                       min="1" max="99999999.99" step="0.01" required>
            </label>

            <label>
                Status
                <select name="status_mobil" id="editStatus" required>
                    <option value="Tersedia">Tersedia</option>
                    <option value="Perawatan">Perawatan</option>
                </select>
            </label>

        </div>

        <div class="car-form-actions">
            <button type="button" class="cancel-btn" id="cancelEditForm">
                Batal
            </button>

            <button type="submit" name="edit_mobil" value="1"
                    class="add-car-btn">
                Simpan Perubahan
            </button>
        </div>

    </form>
</div>


<!-- POPUP KONFIRMASI HAPUS -->
<div class="delete-modal" id="deleteModal">

    <div class="delete-modal-content">

        <div class="delete-modal-icon">
            <i data-lucide="alert-triangle"></i>
        </div>

        <h3>Hapus Data Mobil?</h3>

        <p class="delete-description">
            Apakah kamu yakin ingin menghapus kendaraan berikut?
        </p>

        <div class="delete-car-info">
            <strong id="deleteCarName">Nama Mobil</strong>
            <span id="deleteCarPlate">Nomor Polisi</span>
        </div>

        <div class="delete-warning">
            <i data-lucide="alert-circle"></i>
            <span>Data yang sudah dihapus tidak dapat dikembalikan.</span>
        </div>

        <form method="POST" action="mobil.php">
            <input type="hidden" name="id_mobil" id="deleteCarId">

            <div class="delete-modal-actions">
                <button type="button" class="cancel-btn" id="cancelDelete">
                    Batal
                </button>

                <button type="submit" name="hapus_mobil" value="1"
                        class="confirm-delete-btn">
                    <i data-lucide="trash-2"></i>
                    Hapus Mobil
                </button>
            </div>
        </form>
    </div>
</div>

<!-- POPUP DETAIL MOBIL -->
<div class="detail-modal" id="detailModal">

    <div class="detail-modal-content">

        <div class="detail-modal-header">
            <div>
                <h3>Detail Mobil</h3>
                <p>Informasi lengkap kendaraan ONE RENT.</p>
            </div>

            <button type="button" class="action-btn" id="closeDetail">
                <i data-lucide="x"></i>
            </button>
        </div>

        <div class="detail-car-icon">
            <i data-lucide="car-front"></i>
        </div>

        <h3 class="detail-car-name" id="detailMerk">Nama Mobil</h3>
        <p class="detail-car-id" id="detailId">ID Mobil</p>

        <div class="detail-information">

            <div class="detail-item">
                <span>Nomor Polisi</span>
                <strong id="detailPlat">-</strong>
            </div>

            <div class="detail-item">
                <span>Kapasitas/Kursi</span>
                <strong id="detailSeat">-</strong>
            </div>

            <div class="detail-item">
                <span>Harga Sewa / Hari</span>
                <strong id="detailTarif">-</strong>
            </div>

            <div class="detail-item">
                <span>Status Mobil</span>
                <strong id="detailStatus">-</strong>
            </div>

        </div>

        <button type="button" class="cancel-btn detail-close-btn"
                id="closeDetailBottom">
            Tutup
        </button>

    </div>
</div>
 
            <footer>
                © 2026 ONE RENT
            </footer>

        </section>
    </main>
</div>

<script>


// NOTIFIKASI
function closeAlert(alert) {
    if (!alert || !alert.parentElement) return;

    alert.style.transition = "opacity 0.3s ease, transform 0.3s ease";
    alert.style.opacity = "0";
    alert.style.transform = "translateY(-8px)";

    setTimeout(function() {
        alert.remove();
    }, 300);
}

// Notifikasi hilang otomatis setelah 3 detik
document.querySelectorAll(".alert-message").forEach(function(alert) {
    setTimeout(function() {
        closeAlert(alert);
    }, 3000);
});


    // Tanggal
    const dateElement = document.getElementById("currentDate");

    if (dateElement) {
        dateElement.textContent = new Date().toLocaleDateString("id-ID", {
            day: "numeric",
            month: "long",
            year: "numeric"
        });
    }

    // Sidebar
    function toggleSidebar() {
        document.getElementById("sidebar").classList.toggle("show");
    }

    // Form tambah mobil
    const openForm = document.getElementById("openForm");
    const closeForm = document.getElementById("closeForm");
    const cancelForm = document.getElementById("cancelForm");
    const formPanel = document.getElementById("carFormPanel");

    openForm.addEventListener("click", function() {
        formPanel.classList.add("show");
        formPanel.scrollIntoView({ behavior: "smooth" });
    });

    function hideForm() {
        formPanel.classList.remove("show");
    }

    closeForm.addEventListener("click", hideForm);
    cancelForm.addEventListener("click", hideForm);

    // Pencarian dan filter status
    const searchCar = document.getElementById("searchCar");
    const filterStatus = document.getElementById("filterStatus");
    const tableBody = document.getElementById("carTableBody");
    const tableNote = document.getElementById("tableNote");

    function filterCars() {
        const keyword = searchCar.value.toLowerCase();
        const status = filterStatus.value.toLowerCase();

        const rows = tableBody.querySelectorAll("tr");
        let jumlah = 0;

        rows.forEach(function(row) {
            if (!row.dataset.status) return;

            const text = row.textContent.toLowerCase();
            const rowStatus = row.dataset.status.toLowerCase();

            const cocokKeyword = text.includes(keyword);
            const cocokStatus = status === "" || rowStatus === status;

            if (cocokKeyword && cocokStatus) {
                row.style.display = "";
                jumlah++;
            } else {
                row.style.display = "none";
            }
        });

        tableNote.textContent = "Menampilkan " + jumlah + " kendaraan.";
    }

    searchCar.addEventListener("input", filterCars);
    filterStatus.addEventListener("change", filterCars);


// POPUP DETAIL MOBIL
const detailModal = document.getElementById("detailModal");
const closeDetail = document.getElementById("closeDetail");
const closeDetailBottom = document.getElementById("closeDetailBottom");

document.querySelectorAll(".detail-btn").forEach(function(button) {

    button.addEventListener("click", function() {

        document.getElementById("detailId").textContent = this.dataset.id;
        document.getElementById("detailMerk").textContent = this.dataset.merk;
        document.getElementById("detailPlat").textContent = this.dataset.plat;
        document.getElementById("detailSeat").textContent = this.dataset.seat;

        document.getElementById("detailTarif").textContent =
            "Rp" + Number(this.dataset.tarif).toLocaleString("id-ID");

        document.getElementById("detailStatus").textContent = this.dataset.status;

        detailModal.classList.add("show");
        document.body.style.overflow = "hidden";

    });

});

function hideDetailModal() {
    detailModal.classList.remove("show");
    document.body.style.overflow = "";
}

closeDetail.addEventListener("click", hideDetailModal);
closeDetailBottom.addEventListener("click", hideDetailModal);

detailModal.addEventListener("click", function(event) {
    if (event.target === detailModal) {
        hideDetailModal();
    }
});

// POPUP EDIT MOBIL
const editFormPanel = document.getElementById("editFormPanel");
const closeEditForm = document.getElementById("closeEditForm");
const cancelEditForm = document.getElementById("cancelEditForm");

// Klik tombol edit pada tabel
document.querySelectorAll(".edit-btn").forEach(function(button) {

    button.addEventListener("click", function() {

        // Ambil data dari tombol yang diklik
        document.getElementById("editId").value = this.dataset.id;
        document.getElementById("editMerk").value = this.dataset.merk;
        document.getElementById("editPlat").value = this.dataset.plat;
        document.getElementById("editSeat").value = this.dataset.seat;
        document.getElementById("editTarif").value = this.dataset.tarif;
        document.getElementById("editStatus").value = this.dataset.status;

        // Tampilkan popup
        editFormPanel.classList.add("show");
        document.body.style.overflow = "hidden";
    });

});

// Tutup popup
function hideEditForm() {
    editFormPanel.classList.remove("show");
    document.body.style.overflow = "";
}

closeEditForm.addEventListener("click", hideEditForm);
cancelEditForm.addEventListener("click", hideEditForm);

// Klik area luar popup
editFormPanel.addEventListener("click", function(event) {
    if (event.target === editFormPanel) {
        hideEditForm();
    }
});


// POPUP HAPUS MOBIL
const deleteModal = document.getElementById("deleteModal");
const cancelDelete = document.getElementById("cancelDelete");

const deleteCarId = document.getElementById("deleteCarId");
const deleteCarName = document.getElementById("deleteCarName");
const deleteCarPlate = document.getElementById("deleteCarPlate");

// Klik tombol hapus
document.querySelectorAll(".delete-btn").forEach(function(button) {

    button.addEventListener("click", function() {

        deleteCarId.value = this.dataset.id;
        deleteCarName.textContent = this.dataset.merk;
        deleteCarPlate.textContent = this.dataset.plat;

        deleteModal.classList.add("show");
        document.body.style.overflow = "hidden";

    });

});

// Tutup popup
function hideDeleteModal() {
    deleteModal.classList.remove("show");
    document.body.style.overflow = "";
}

cancelDelete.addEventListener("click", hideDeleteModal);

// Klik area luar popup
deleteModal.addEventListener("click", function(event) {
    if (event.target === deleteModal) {
        hideDeleteModal();
    }
});

    // Ikon
    lucide.createIcons();
</script>

</body>
</html>