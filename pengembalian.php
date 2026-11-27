<?php
// MODUL PENGEMBALIAN: mencatat pengembalian, menghitung keterlambatan, menyinkronkan status mobil, dan menampilkan riwayat.
require_once 'module_helper.php';
?>

<style>
.status.warning {
    background: rgba(239, 68, 68, 0.15);
    color: #ef4444;
}

.status.success {
    background: rgba(34, 197, 94, 0.15);
    color: #22c55e;
}
</style>

<?php

// Proses tambah pengembalian
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (($_POST['action'] ?? '') !== 'save') {
            throw new Exception('Aksi pengembalian tidak dikenali.');
        }

        $tid = trim($_POST['id_transaksi'] ?? '');
        $date = trim($_POST['tanggal_kembali'] ?? '');

        if ($tid === '' || $date === '') {
            throw new Exception('Pilih transaksi dan tanggal pengembalian.');
        }

        if (!isValidDate($date)) {
            throw new Exception('Format tanggal pengembalian tidak valid.');
        }

        if ($date > todayDate()) {
            throw new Exception('Tanggal pengembalian tidak boleh melebihi hari ini.');
        }

        $tr = oneParam(
            $conn,
            'SELECT t.id_transaksi,t.tanggal_mulai,t.tanggal_selesai,r.id_mobil
             FROM transaksisewa t
             JOIN reservasi r ON r.id_reservasi=t.id_reservasi
             WHERE t.id_transaksi=?',
            's',
            [$tid]
        );

        if (!$tr) {
            throw new Exception('Transaksi tidak ditemukan.');
        }

        if ($date < $tr['tanggal_mulai']) {
            throw new Exception('Tanggal kembali tidak boleh sebelum tanggal mulai sewa (' . $tr['tanggal_mulai'] . ').');
        }

        if (
            oneParam(
                $conn,
                'SELECT id_pengembalian FROM pengembalian WHERE id_transaksi=?',
                's',
                [$tid]
            )
        ) {
            throw new Exception('Transaksi ini sudah memiliki data pengembalian.');
        }

        $late = max(
            0,
            (int)((strtotime($date) - strtotime($tr['tanggal_selesai'])) / 86400)
        );

        $status = $late > 0 ? 'TERLAMBAT' : 'DIKEMBALIKAN';

        $id = nextId($conn, 'pengembalian', 'id_pengembalian', 'KMB');

        mysqli_begin_transaction($conn);

        try {
            runStmt(
                $conn,
                'INSERT INTO pengembalian
                (id_pengembalian,id_transaksi,tanggal_kembali,keterlambatan,status_pengembalian)
                VALUES(?,?,?,?,?)',
                'sssis',
                [$id, $tid, $date, $late, $status]
            );

            runStmt(
                $conn,
                "UPDATE mobil SET status_mobil='Tersedia'
                 WHERE id_mobil=? AND status_mobil='Disewa'",
                's',
                [$tr['id_mobil']]
            );

            mysqli_commit($conn);

        } catch (Throwable $e) {
            mysqli_rollback($conn);
            throw $e;
        }

        flashMessage(
            'success',
            "Pengembalian $id berhasil dicatat. Keterlambatan: $late hari."
        );
    }

} catch (Throwable $e) {
    flashMessage('error', $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: pengembalian.php');
    exit;
}

// Memperbaiki status mobil yang belum sinkron dengan transaksi aktif
// tanpa mengubah mobil yang sedang dalam perawatan.
$sync = mysqli_query(
    $conn,
    "UPDATE mobil m
     SET m.status_mobil='Disewa'
     WHERE m.status_mobil<>'Perawatan'
     AND EXISTS (
         SELECT 1
         FROM reservasi r
         JOIN transaksisewa t ON t.id_reservasi=r.id_reservasi
         LEFT JOIN pengembalian k ON k.id_transaksi=t.id_transaksi
         WHERE r.id_mobil=m.id_mobil
         AND k.id_pengembalian IS NULL
     )"
);

if (!$sync) {
    throw new Exception(mysqli_error($conn));
}

$syncReturned = mysqli_query(
    $conn,
    "UPDATE mobil m
     SET m.status_mobil='Tersedia'
     WHERE m.status_mobil='Disewa'
     AND NOT EXISTS (
         SELECT 1
         FROM reservasi r
         JOIN transaksisewa t ON t.id_reservasi=r.id_reservasi
         LEFT JOIN pengembalian k ON k.id_transaksi=t.id_transaksi
         WHERE r.id_mobil=m.id_mobil
         AND k.id_pengembalian IS NULL
     )"
);

if (!$syncReturned) {
    throw new Exception(mysqli_error($conn));
}

// Mengambil transaksi yang belum dikembalikan
$transactions = rows(
    $conn,
    'SELECT t.id_transaksi,t.tanggal_selesai,
            p.nama_penyewa,m.merk_mobil
     FROM transaksisewa t
     JOIN reservasi r ON t.id_reservasi=r.id_reservasi
     JOIN penyewa p ON r.id_penyewa=p.id_penyewa
     JOIN mobil m ON r.id_mobil=m.id_mobil
     LEFT JOIN pengembalian k ON t.id_transaksi=k.id_transaksi
     WHERE k.id_pengembalian IS NULL
     ORDER BY t.tanggal_selesai'
);

// Mengambil riwayat pengembalian
$data = rows(
    $conn,
    'SELECT k.*,t.id_transaksi,p.nama_penyewa,m.merk_mobil
     FROM pengembalian k
     JOIN transaksisewa t ON k.id_transaksi=t.id_transaksi
     JOIN reservasi r ON t.id_reservasi=r.id_reservasi
     JOIN penyewa p ON r.id_penyewa=p.id_penyewa
     JOIN mobil m ON r.id_mobil=m.id_mobil
     ORDER BY k.tanggal_kembali DESC'
);

beginPage(
    'Pengembalian',
    'Catat pengembalian mobil dari transaksi sewa.',
    'pengembalian'
);
?>

<div class="module-intro">
    <div>
        <h2>Data Pengembalian</h2>
        <p>Sistem menghitung keterlambatan berdasarkan tanggal selesai sewa.</p>
    </div>
</div>

<div class="module-panel module-form-wrap">
    <h3>Tambah Pengembalian</h3>

    <form method="POST" class="module-form">
        <input type="hidden" name="action" value="save">

        <label>
            Transaksi Sewa
            <select name="id_transaksi" required>
                <option value="">Pilih transaksi yang belum dikembalikan</option>

                <?php foreach ($transactions as $t): ?>
                    <option value="<?=h($t['id_transaksi'])?>">
                        <?=h(
                            $t['id_transaksi'] . ' - ' .
                            $t['nama_penyewa'] . ' - ' .
                            $t['merk_mobil'] . ' (Batas: ' .
                            $t['tanggal_selesai'] . ')'
                        )?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Tanggal Kembali
            <input
                type="date"
                name="tanggal_kembali"
                value="<?=h(todayDate())?>"
                max="<?=h(todayDate())?>"
                required
            >
        </label>

        <div class="form-actions">
            <button class="primary-btn" type="submit">
                <i data-lucide="plus"></i>Simpan Pengembalian
            </button>
        </div>
    </form>
</div>

<div class="module-panel">
    <div class="module-toolbar">
        <div>
            <h3>Riwayat Pengembalian</h3>
            <p class="muted">Total <?=count($data)?> pengembalian</p>
        </div>

        <input
            id="searchTable"
            type="search"
            placeholder="Cari pengembalian, pelanggan..."
        >
    </div>

    <div class="module-table">
        <table id="dataTable">
            <thead>
                <tr>
                    <th>ID Pengembalian</th>
                    <th>ID Transaksi</th>
                    <th>Pelanggan</th>
                    <th>Mobil</th>
                    <th>Tanggal Kembali</th>
                    <th>Terlambat</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$data): ?>
                    <tr>
                        <td colspan="7" class="empty-row">Belum ada pengembalian.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($data as $r): ?>
                    <tr>
                        <td><?=h($r['id_pengembalian'])?></td>
                        <td><?=h($r['id_transaksi'])?></td>
                        <td><?=h($r['nama_penyewa'])?></td>
                        <td><?=h($r['merk_mobil'])?></td>
                        <td><?=h($r['tanggal_kembali'])?></td>
                        <td><?=h($r['keterlambatan'])?> hari</td>
                        <td>
                            <span class="status <?=$r['status_pengembalian'] === 'TERLAMBAT' ? 'warning' : 'success'?>">
                                <?=h($r['status_pengembalian'])?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Pencarian riwayat pengembalian
document.getElementById('searchTable').addEventListener('input', function() {
    const q = this.value.toLowerCase();

    document.querySelectorAll('#dataTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<?php endPage(); ?>
