
<?php
// HELPER MODUL: fungsi bersama untuk autentikasi, database, validasi tanggal, notifikasi, dan kerangka halaman.
// Pengaturan waktu diseragamkan untuk seluruh modul.

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/koneksi.php';


function h($v)
{
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}


function todayDate()
{
    return date('Y-m-d');
}


function isValidDate($value)
{
    if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
        return false;
    }

    $d = DateTime::createFromFormat('!Y-m-d', $value);
    $errors = DateTime::getLastErrors();

    return $d &&
        (!$errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)) &&
        $d->format('Y-m-d') === $value;
}


function nextId($conn, $table, $column, $prefix)
{
    $allowed = [
        'reservasi'     => 'id_reservasi',
        'transaksisewa' => 'id_transaksi',
        'pembayaran'    => 'id_pembayaran',
        'pengembalian'  => 'id_pengembalian',
        'denda'         => 'id_denda'
    ];

    if (!isset($allowed[$table]) || $allowed[$table] !== $column) {
        throw new Exception('Kolom ID tidak valid.');
    }

    $sql = "SELECT `$column` FROM `$table`
            WHERE `$column` LIKE CONCAT(?, '%')
            ORDER BY CAST(SUBSTRING(`$column`, ?) AS UNSIGNED) DESC
            LIMIT 1";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception('Gagal menyiapkan nomor ID.');
    }

    $offset = strlen($prefix) + 1;

    mysqli_stmt_bind_param($stmt, 'si', $prefix, $offset);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $last = $result ? mysqli_fetch_assoc($result) : null;

    mysqli_stmt_close($stmt);

    $number = $last
        ? (int)substr($last[$column], strlen($prefix)) + 1
        : 1;

    return $prefix . str_pad((string)$number, 3, '0', STR_PAD_LEFT);
}


function flashMessage($type, $message)
{
    $_SESSION['flash_type'] = $type;
    $_SESSION['flash_message'] = $message;
}


function getFlash()
{
    $data = [
        $_SESSION['flash_type'] ?? '',
        $_SESSION['flash_message'] ?? ''
    ];

    unset($_SESSION['flash_type'], $_SESSION['flash_message']);

    return $data;
}


function runStmt($conn, $sql, $types = '', $params = [])
{
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    if ($types !== '') {
        $bind = [$types];

        foreach ($params as $key => &$value) {
            $bind[] = &$value;
        }

        call_user_func_array([$stmt, 'bind_param'], $bind);
        unset($value);
    }

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);

        throw new Exception($error);
    }

    $affected = mysqli_stmt_affected_rows($stmt);

    mysqli_stmt_close($stmt);

    return $affected;
}


function one($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new Exception(mysqli_error($conn));
    }

    return mysqli_fetch_assoc($result);
}


function oneParam($conn, $sql, $types, $params)
{
    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception(mysqli_error($conn));
    }

    $bind = [$types];

    foreach ($params as $key => &$value) {
        $bind[] = &$value;
    }

    call_user_func_array([$stmt, 'bind_param'], $bind);
    unset($value);

    if (!mysqli_stmt_execute($stmt)) {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);

        throw new Exception($error);
    }

    $result = mysqli_stmt_get_result($stmt);
    $row = $result ? mysqli_fetch_assoc($result) : null;

    mysqli_stmt_close($stmt);

    return $row;
}


function rows($conn, $sql)
{
    $result = mysqli_query($conn, $sql);

    if (!$result) {
        throw new Exception(mysqli_error($conn));
    }

    return mysqli_fetch_all($result, MYSQLI_ASSOC);
}


function beginPage($title, $subtitle, $active)
{
    // Fungsi ini memakai koneksi global yang dibuat oleh koneksi.php.
    global $conn;

    $admin = $_SESSION['nama_admin'] ?? 'Administrator';

    $menu = [
        ['dashboard.php', 'layout-dashboard', 'Dashboard', 'dashboard'],
        ['mobil.php', 'car-front', 'Data Mobil', 'mobil'],
        ['pelanggan.php', 'users', 'Data Pelanggan', 'pelanggan'],
        ['reservasi.php', 'calendar-check', 'Reservasi', 'reservasi'],
        ['transaksi.php', 'clipboard-list', 'Transaksi Sewa', 'transaksi'],
        ['pembayaran.php', 'wallet', 'Pembayaran', 'pembayaran'],
        ['pengembalian.php', 'undo-2', 'Pengembalian', 'pengembalian'],
        ['denda.php', 'receipt', 'Denda', 'denda']
    ];

    echo '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>' . h($title) . ' - ONE RENT</title>';

    echo '<link rel="stylesheet" href="style.css"><link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet"><script src="https://unpkg.com/lucide@latest"></script>';

    echo '<style>
    .module-content{padding:28px 30px}.module-intro{display:flex;justify-content:space-between;align-items:center;gap:15px;margin-bottom:22px}.module-intro h2{font-size:21px;font-weight:600}.module-intro p{color:var(--muted);font-size:12px;margin-top:5px}
    .primary-btn,.secondary-btn,.danger-btn{border:0;border-radius:7px;padding:11px 16px;font:600 12px Outfit;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:7px}.primary-btn{background:var(--peach);color:var(--navy)}.secondary-btn{background:transparent;color:#fff;border:1px solid var(--border)}.danger-btn{background:#D9534F;color:#fff}
    .module-panel{background:var(--card);border:1px solid var(--border);border-radius:11px;padding:22px;margin-bottom:22px}.module-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:18px}.module-toolbar h3{font-size:15px}.module-toolbar input,.module-toolbar select,.module-form input,.module-form select,.module-form textarea{background:var(--navy);color:#fff;border:1px solid var(--border);border-radius:7px;padding:11px 12px;font:12px Outfit;outline:none}.module-toolbar input{min-width:220px}.module-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.module-form label{display:flex;flex-direction:column;gap:7px;color:var(--muted);font-size:12px}.module-form input,.module-form select,.module-form textarea{width:100%}.module-form textarea{min-height:75px;resize:vertical}.form-actions{grid-column:1/-1;display:flex;justify-content:flex-end;gap:10px;margin-top:5px}
    .module-table{overflow-x:auto}.module-table table{min-width:760px}.module-table th{background:var(--navy);color:var(--muted)}.module-table td,.module-table th{padding:13px 12px}.module-table td{border-bottom:1px solid var(--border)}.inline-actions{display:flex;gap:6px}.inline-actions button{background:var(--navy);border:1px solid var(--border);color:#fff;padding:7px 9px;border-radius:6px;cursor:pointer;font:11px Outfit}.inline-actions .del{color:#F08080}.module-alert{padding:13px 16px;border-radius:8px;margin-bottom:18px;font-size:12px}.module-alert.success{background:rgba(85,184,137,.12);color:#70D0A3}.module-alert.error{background:rgba(217,83,79,.12);color:#F08080}
    .status.success {
        background: rgba(85,184,137,.15) !important;
        color: #55B889 !important;
        border: 1px solid rgba(85,184,137,.25) !important;
    }
    .muted{color:var(--muted)}.module-form-wrap{margin-bottom:22px}.module-form-wrap h3{margin-bottom:18px;font-size:15px}.empty-row{text-align:center;color:var(--muted);padding:25px}.sidebar .brand p{color:var(--muted)}.menu-toggle{flex-shrink:0}.topbar{gap:14px}.page-heading{flex:1}
    @media(max-width:768px){.module-content{padding:18px 15px}.module-intro{align-items:flex-start;flex-direction:column}.module-toolbar{align-items:stretch;flex-direction:column}.module-toolbar input{width:100%;min-width:0}.module-form{grid-template-columns:1fr}.form-actions{grid-column:1}.module-panel{padding:16px}}
    </style></head><body><div class="container">';

    echo '<aside class="sidebar" id="sidebar"><div class="brand"><div class="brand-icon"><i data-lucide="car-front"></i></div><div><h2>ONE RENT</h2><p>CAR RENTAL MANAGEMENT</p></div></div><div class="menu-title">MENU UTAMA</div><nav class="menu">';

    foreach ($menu as $item) {
        $cls = $active === $item[3] ? 'menu-item active' : 'menu-item';

        echo '<a href="' . h($item[0]) . '" class="' . $cls . '"><i data-lucide="' . h($item[1]) . '"></i>' . h($item[2]) . '</a>';
    }

    echo '</nav><div class="menu-title">PENGATURAN</div><nav class="menu"><a href="profil.php" class="menu-item' . ($active === 'profil' ? ' active' : '') . '"><i data-lucide="settings"></i>Profil Admin</a></nav>';

    echo '<div class="sidebar-bottom"><div class="admin-mini"><div class="avatar">' . h(strtoupper(substr($admin, 0, 1))) . '</div><div><strong>' . h($admin) . '</strong><small>Admin</small></div></div><a href="logout.php" class="logout"><i data-lucide="log-out"></i>Keluar</a></div></aside>';

    echo '<main class="main-content"><header class="topbar"><button class="menu-toggle" onclick="document.getElementById(\'sidebar\').classList.toggle(\'show\')"><i data-lucide="menu"></i></button><div class="page-heading"><h2>' . h($title) . '</h2><p>' . h($subtitle) . '</p></div></header><section class="module-content">';

    [$type, $message] = getFlash();

    if ($message) {
        echo '<div class="module-alert ' . h($type) . '">' . h($message) . '</div>';
    }


    // Peringatan otomatis untuk transaksi yang melewati tanggal selesai
    // tetapi belum memiliki catatan pengembalian.
    try {
        if (isset($conn) && $conn instanceof mysqli) {
            $sqlOverdue = "SELECT t.id_transaksi, t.tanggal_selesai, p.nama_penyewa, m.merk_mobil, DATEDIFF(CURDATE(), t.tanggal_selesai) AS hari_terlambat
                FROM transaksisewa t
                JOIN reservasi r ON r.id_reservasi = t.id_reservasi
                JOIN penyewa p ON p.id_penyewa = r.id_penyewa
                JOIN mobil m ON m.id_mobil = r.id_mobil
                LEFT JOIN pengembalian k ON k.id_transaksi = t.id_transaksi
                WHERE k.id_pengembalian IS NULL AND t.tanggal_selesai < CURDATE()
                ORDER BY t.tanggal_selesai ASC";

            $overdueResult = mysqli_query($conn, $sqlOverdue);

            if ($overdueResult && mysqli_num_rows($overdueResult) > 0) {
                echo '<div class="module-alert error overdue-alert"><strong>⚠ Peringatan pengembalian terlambat</strong><div style="margin-top:8px">';

                while ($late = mysqli_fetch_assoc($overdueResult)) {
                    echo '<div style="padding:5px 0;border-top:1px solid rgba(240,128,128,.2)">'
                        . '<strong>' . h($late['nama_penyewa']) . '</strong> — ' . h($late['merk_mobil'])
                        . ' | Transaksi ' . h($late['id_transaksi'])
                        . ' | Batas: ' . h($late['tanggal_selesai'])
                        . ' | Terlambat <strong>' . (int)$late['hari_terlambat'] . ' hari</strong>'
                        . ' — <a href="pengembalian.php" style="color:inherit;text-decoration:underline">Catat pengembalian</a></div>';
                }

                echo '</div></div>';
            }

            if ($overdueResult) {
                mysqli_free_result($overdueResult);
            }
        }
    } catch (Throwable $e) {
        // Peringatan tidak boleh membuat halaman utama gagal dimuat.
        error_log('ONE RENT overdue notice: ' . $e->getMessage());
    }
}


function endPage()
{
    echo '</section><footer>© ' . date('Y') . ' ONE RENT - Car Rental Management</footer></main></div><script>
    document.querySelectorAll(".module-alert, .alert-message, .customer-alert").forEach(function(alert){setTimeout(function(){alert.style.transition="opacity .3s ease, transform .3s ease";alert.style.opacity="0";alert.style.transform="translateY(-8px)";setTimeout(function(){alert.remove();},300);},3000);});
    if(window.lucide) lucide.createIcons();
    </script></body></html>';
}


function optionRows($items, $valueKey, $labelKey, $placeholder = 'Pilih data')
{
    $html = '<option value="">' . h($placeholder) . '</option>';

    foreach ($items as $r) {
        $html .= '<option value="' . h($r[$valueKey]) . '">' . h($r[$labelKey]) . '</option>';
    }

    return $html;
}
