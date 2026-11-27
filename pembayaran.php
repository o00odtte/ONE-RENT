<?php
// MODUL PEMBAYARAN: validasi dan pencatatan pembayaran transaksi maupun denda serta riwayatnya.

require_once 'module_helper.php';

// Proses tambah pembayaran
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (($_POST['action'] ?? '') !== 'save') {
            throw new Exception('Aksi pembayaran tidak dikenali.');
        }

        $tid = trim($_POST['id_transaksi'] ?? '');
        $date = trim($_POST['tanggal_pembayaran'] ?? '');
        $jenis = $_POST['jenis_pembayaran'] ?? '';
        $method = $_POST['metode_pembayaran'] ?? '';
        $amountRaw = trim((string)($_POST['jumlah_pembayaran'] ?? ''));

        if (
            $tid === '' || $date === '' || $amountRaw === '' ||
            !in_array($jenis, ['PELUNASAN', 'DENDA'], true) ||
            !in_array($method, ['CASH', 'TRANSFER', 'E-WALLET'], true)
        ) {
            throw new Exception('Periksa kembali seluruh data pembayaran.');
        }

        if (!isValidDate($date)) {
            throw new Exception('Format tanggal pembayaran tidak valid.');
        }

        if ($date > todayDate()) {
            throw new Exception('Tanggal pembayaran tidak boleh melebihi hari ini.');
        }

        if (
            !is_numeric($amountRaw) ||
            !is_finite((float)$amountRaw) ||
            (float)$amountRaw <= 0 ||
            (float)$amountRaw > 99999999.99
        ) {
            throw new Exception('Jumlah pembayaran harus berupa angka lebih dari nol dan tidak melebihi batas sistem.');
        }

        $amount = (float)$amountRaw;

        $tr = oneParam(
            $conn,
            'SELECT t.id_transaksi,t.total_harga,r.tanggal_reservasi
             FROM transaksisewa t
             JOIN reservasi r ON r.id_reservasi=t.id_reservasi
             WHERE t.id_transaksi=?',
            's',
            [$tid]
        );

        if (!$tr) {
            throw new Exception('Transaksi tidak ditemukan.');
        }

        if ($date < $tr['tanggal_reservasi']) {
            throw new Exception('Tanggal pembayaran tidak boleh sebelum tanggal reservasi (' . $tr['tanggal_reservasi'] . ').');
        }

        if ($jenis === 'PELUNASAN') {
            if (
                oneParam(
                    $conn,
                    "SELECT id_pembayaran FROM pembayaran
                     WHERE id_transaksi=? AND jenis_pembayaran='PELUNASAN'
                     LIMIT 1",
                    's',
                    [$tid]
                )
            ) {
                throw new Exception('Pelunasan untuk transaksi ini sudah tercatat. Pelunasan hanya boleh satu kali.');
            }

            if (abs($amount - (float)$tr['total_harga']) > 0.009) {
                throw new Exception(
                    'Jumlah pelunasan harus sama dengan total harga sewa: Rp ' .
                    number_format((float)$tr['total_harga'], 0, ',', '.') . '.'
                );
            }

        } else {
            if (
                oneParam(
                    $conn,
                    "SELECT id_pembayaran FROM pembayaran
                     WHERE id_transaksi=? AND jenis_pembayaran='DENDA'
                     LIMIT 1",
                    's',
                    [$tid]
                )
            ) {
                throw new Exception('Pembayaran denda untuk transaksi ini sudah tercatat.');
            }

            $fine = oneParam(
                $conn,
                "SELECT SUM(d.jumlah_denda) AS total_denda,
                        MAX(d.tanggal_denda) AS tanggal_denda,
                        k.tanggal_kembali
                 FROM denda d
                 JOIN pengembalian k ON d.id_pengembalian=k.id_pengembalian
                 WHERE k.id_transaksi=?
                 GROUP BY k.id_transaksi,k.tanggal_kembali",
                's',
                [$tid]
            );

            if (!$fine || (float)$fine['total_denda'] <= 0) {
                throw new Exception('Belum ada denda yang tercatat untuk transaksi ini.');
            }

            $minDate = max($fine['tanggal_kembali'], $fine['tanggal_denda']);

            if ($date < $minDate) {
                throw new Exception('Tanggal pembayaran denda tidak boleh sebelum tanggal pengembalian dan tanggal denda (' . $minDate . ').');
            }

            if (abs($amount - (float)$fine['total_denda']) > 0.009) {
                throw new Exception(
                    'Jumlah pembayaran denda harus sama dengan total denda: Rp ' .
                    number_format((float)$fine['total_denda'], 0, ',', '.') . '.'
                );
            }
        }

        $id = nextId($conn, 'pembayaran', 'id_pembayaran', 'BYR');

        runStmt(
            $conn,
            "INSERT INTO pembayaran
            (id_pembayaran,id_transaksi,tanggal_pembayaran,jenis_pembayaran,
             jumlah_pembayaran,metode_pembayaran,status_pembayaran)
             VALUES(?,?,?,?,?,?,'LUNAS')",
            'ssssds',
            [$id, $tid, $date, $jenis, $amount, $method]
        );

        flashMessage('success', "Pembayaran $id berhasil dicatat.");
    }
} catch (Throwable $e) {
    flashMessage('error', $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: pembayaran.php');
    exit;
}

// Mengambil daftar transaksi
$transactions = rows(
    $conn,
    'SELECT t.id_transaksi,t.total_harga,t.tanggal_mulai,
            r.tanggal_reservasi,p.nama_penyewa,m.merk_mobil
     FROM transaksisewa t
     JOIN reservasi r ON t.id_reservasi=r.id_reservasi
     JOIN penyewa p ON r.id_penyewa=p.id_penyewa
     JOIN mobil m ON r.id_mobil=m.id_mobil
     ORDER BY t.id_transaksi DESC'
);

// Mengambil riwayat pembayaran
$data = rows(
    $conn,
    'SELECT b.*,p.nama_penyewa,m.merk_mobil
     FROM pembayaran b
     JOIN transaksisewa t ON b.id_transaksi=t.id_transaksi
     JOIN reservasi r ON t.id_reservasi=r.id_reservasi
     JOIN penyewa p ON r.id_penyewa=p.id_penyewa
     JOIN mobil m ON r.id_mobil=m.id_mobil
     ORDER BY b.tanggal_pembayaran DESC,b.id_pembayaran DESC'
);

beginPage('Pembayaran', 'Catat pelunasan sewa dan pembayaran denda.', 'pembayaran');
?>

<div class="module-intro">
    <div>
        <h2>Data Pembayaran</h2>
        <p>Pelunasan sewa dicatat satu kali. Pembayaran denda dicatat terpisah jika ada.</p>
    </div>
</div>

<div class="module-panel module-form-wrap">
    <h3>Tambah Pembayaran</h3>

    <form method="POST" class="module-form">
        <input type="hidden" name="action" value="save">

        <label>
            Transaksi Sewa
            <select name="id_transaksi" required>
                <option value="">Pilih transaksi</option>
                <?php foreach ($transactions as $t): ?>
                    <option value="<?=h($t['id_transaksi'])?>">
                        <?=h(
                            $t['id_transaksi'] . ' - ' .
                            $t['nama_penyewa'] . ' - ' .
                            $t['merk_mobil'] . ' (Rp ' .
                            number_format((float)$t['total_harga'], 0, ',', '.') . ')'
                        )?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Jenis Pembayaran
            <select name="jenis_pembayaran" required>
                <option value="PELUNASAN">Pelunasan</option>
                <option value="DENDA">Denda</option>
            </select>
        </label>

        <label>
            Tanggal Pembayaran
            <input
                type="date"
                name="tanggal_pembayaran"
                value="<?=h(todayDate())?>"
                max="<?=h(todayDate())?>"
                required
            >
        </label>

        <label>
            Jumlah Pembayaran (Rp)
            <input
                type="number"
                name="jumlah_pembayaran"
                min="0.01"
                max="99999999.99"
                step="0.01"
                required
            >
        </label>

        <label>
            Metode Pembayaran
            <select name="metode_pembayaran" required>
                <option value="">Pilih metode</option>
                <option value="CASH">Cash</option>
                <option value="TRANSFER">Transfer</option>
                <option value="E-WALLET">E-Wallet</option>
            </select>
        </label>

        <div class="form-actions">
            <button class="primary-btn" type="submit">
                <i data-lucide="plus"></i>Simpan Pembayaran
            </button>
        </div>
    </form>
</div>

<div class="module-panel">
    <div class="module-toolbar">
        <div>
            <h3>Riwayat Pembayaran</h3>
            <p class="muted">Total <?=count($data)?> pembayaran</p>
        </div>

        <input
            id="searchTable"
            type="search"
            placeholder="Cari pembayaran, pelanggan..."
        >
    </div>

    <div class="module-table">
        <table id="dataTable">
            <thead>
                <tr>
                    <th>ID Pembayaran</th>
                    <th>ID Transaksi</th>
                    <th>Pelanggan</th>
                    <th>Mobil</th>
                    <th>Tanggal</th>
                    <th>Jenis</th>
                    <th>Jumlah</th>
                    <th>Metode</th>
                    <th>Status</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$data): ?>
                    <tr>
                        <td colspan="9" class="empty-row">Belum ada pembayaran.</td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($data as $r): ?>
                    <tr>
                        <td><?=h($r['id_pembayaran'])?></td>
                        <td><?=h($r['id_transaksi'])?></td>
                        <td><?=h($r['nama_penyewa'])?></td>
                        <td><?=h($r['merk_mobil'])?></td>
                        <td><?=h($r['tanggal_pembayaran'])?></td>
                        <td><?=h($r['jenis_pembayaran'])?></td>
                        <td>Rp <?=number_format((float)$r['jumlah_pembayaran'], 0, ',', '.')?></td>
                        <td><?=h($r['metode_pembayaran'])?></td>
                        <td><span class="status success">LUNAS</span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Pencarian riwayat pembayaran
document.getElementById('searchTable').addEventListener('input', function() {
    const q = this.value.toLowerCase();

    document.querySelectorAll('#dataTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
});
</script>

<?php endPage(); ?>
