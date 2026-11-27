<?php
// MODUL RESERVASI: mengelola data pemesanan mobil oleh pelanggan dan menampilkan daftar reservasi.

require_once 'module_helper.php';

// Mengambil satu data
function ambilSatu($sql, $types, $params) {
    global $conn;
    return oneParam($conn, $sql, $types, $params);
}

// Mengambil banyak data
function ambilBanyak($sql) {
    global $conn;
    return rows($conn, $sql);
}

// Membuat nomor reservasi berikutnya
function buatIdReservasi() {
    global $conn;
    return nextId($conn, 'reservasi', 'id_reservasi', 'RSV');
}

// Proses tambah dan hapus reservasi
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $customer = trim($_POST['id_penyewa'] ?? '');
            $car = trim($_POST['id_mobil'] ?? '');
            $date = trim($_POST['tanggal_reservasi'] ?? '');

            if ($customer === '' || $car === '' || $date === '') {
                throw new Exception('Lengkapi semua data reservasi.');
            }

            if (!isValidDate($date)) {
                throw new Exception('Format tanggal reservasi tidak valid.');
            }

            if ($date !== todayDate()) {
                throw new Exception('Tanggal reservasi harus sesuai dengan tanggal hari ini.');
            }

            if (!ambilSatu(
                'SELECT id_penyewa FROM penyewa WHERE id_penyewa=?',
                's',
                [$customer]
            )) {
                throw new Exception('Pelanggan tidak ditemukan.');
            }

            $mobil = ambilSatu(
                'SELECT id_mobil,status_mobil FROM mobil WHERE id_mobil=?',
                's',
                [$car]
            );

            if (!$mobil) {
                throw new Exception('Mobil tidak ditemukan.');
            }

            if (strtolower($mobil['status_mobil']) !== 'tersedia') {
                throw new Exception('Mobil tidak tersedia untuk reservasi.');
            }

            $pending = ambilSatu(
                "SELECT r.id_reservasi
                 FROM reservasi r
                 LEFT JOIN transaksisewa t ON t.id_reservasi = r.id_reservasi
                 WHERE r.id_mobil = ? AND t.id_transaksi IS NULL
                 LIMIT 1",
                's',
                [$car]
            );

            if ($pending) {
                throw new Exception('Mobil sudah memiliki reservasi yang belum diproses menjadi transaksi.');
            }

            $id = buatIdReservasi();
            $admin = $_SESSION['id_admin'] ?? null;

            $stmt = $conn->prepare(
                "INSERT INTO reservasi
                (id_reservasi,id_admin,id_penyewa,id_mobil,tanggal_reservasi,status_reservasi)
                VALUES (?,?,?,?,?,'disetujui')"
            );

            if (!$stmt) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param('sssss', $id, $admin, $customer, $car, $date);

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $stmt->close();

            flashMessage('success', "Reservasi berhasil ditambahkan dengan ID $id.");

        } elseif ($action === 'delete') {
            $id = trim($_POST['id'] ?? '');

            if ($id === '') {
                throw new Exception('ID reservasi tidak valid.');
            }

            if (!ambilSatu(
                'SELECT id_reservasi FROM reservasi WHERE id_reservasi=?',
                's',
                [$id]
            )) {
                throw new Exception('Reservasi tidak ditemukan.');
            }

            if (ambilSatu(
                'SELECT id_transaksi FROM transaksisewa WHERE id_reservasi=?',
                's',
                [$id]
            )) {
                throw new Exception('Reservasi sudah memiliki transaksi dan tidak dapat dihapus.');
            }

            $stmt = $conn->prepare('DELETE FROM reservasi WHERE id_reservasi=?');

            if (!$stmt) {
                throw new Exception($conn->error);
            }

            $stmt->bind_param('s', $id);

            if (!$stmt->execute()) {
                throw new Exception($stmt->error);
            }

            $stmt->close();

            flashMessage('success', 'Data reservasi berhasil dihapus.');

        } else {
            throw new Exception('Aksi tidak dikenali.');
        }
    }
} catch (Throwable $e) {
    flashMessage('error', $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Location: reservasi.php');
    exit;
}

// Mengambil data untuk halaman reservasi
$customers = ambilBanyak(
    'SELECT id_penyewa,nama_penyewa,no_hp
     FROM penyewa
     ORDER BY nama_penyewa'
);

$cars = ambilBanyak(
    "SELECT m.id_mobil,m.merk_mobil,m.plat_kendaraan,m.tarif_sewa
     FROM mobil m
     WHERE LOWER(m.status_mobil)='tersedia'
     AND NOT EXISTS (
         SELECT 1 FROM reservasi r
         LEFT JOIN transaksisewa t ON t.id_reservasi=r.id_reservasi
         WHERE r.id_mobil=m.id_mobil AND t.id_transaksi IS NULL
     )
     ORDER BY m.merk_mobil"
);

$data = ambilBanyak(
    'SELECT r.*,p.nama_penyewa,p.no_hp,m.merk_mobil,m.plat_kendaraan
     FROM reservasi r
     LEFT JOIN penyewa p ON r.id_penyewa=p.id_penyewa
     LEFT JOIN mobil m ON r.id_mobil=m.id_mobil
     ORDER BY r.tanggal_reservasi DESC,r.id_reservasi DESC'
);

beginPage(
    'Reservasi',
    'Kelola data reservasi penyewaan mobil.',
    'reservasi'
);
?>

<div class="module-intro">
    <div>
        <h2>Data Reservasi</h2>
        <p>Reservasi dicatat untuk pelanggan yang sudah memastikan penyewaan mobil.</p>
    </div>
</div>

<div class="module-panel module-form-wrap">
    <h3>Tambah Reservasi</h3>

    <form method="POST" class="module-form">
        <input type="hidden" name="action" value="save">

        <label>
            Nama Pelanggan
            <select name="id_penyewa" required>
                <option value="">Pilih pelanggan</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?=h($customer['id_penyewa'])?>">
                        <?=h($customer['nama_penyewa'])?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Mobil
            <select name="id_mobil" required>
                <option value="">Pilih mobil tersedia</option>
                <?php foreach ($cars as $c): ?>
                    <option value="<?=h($c['id_mobil'])?>">
                        <?=h($c['merk_mobil'].' - '.$c['plat_kendaraan'].' ('.$c['id_mobil'].')')?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            Tanggal Reservasi
            <input type="date" name="tanggal_reservasi"
                   value="<?=todayDate()?>"
                   min="<?=todayDate()?>"
                   max="<?=todayDate()?>"
                   required>
        </label>

        <label>
            Status Reservasi
            <input value="Disetujui" disabled>
            <small class="muted">
                Reservasi hanya dicatat untuk pelanggan yang sudah fix menyewa.
            </small>
        </label>

        <div class="form-actions">
            <button class="primary-btn" type="submit">
                <i data-lucide="plus"></i>
                Simpan Reservasi
            </button>
        </div>
    </form>
</div>

<div class="module-panel">
    <div class="module-toolbar">
        <div>
            <h3>Daftar Reservasi</h3>
            <p class="muted">Total <?=count($data)?> reservasi</p>
        </div>

        <input type="search" id="searchTable"
               placeholder="Cari pelanggan, mobil, ID...">
    </div>

    <div class="module-table">
        <table id="dataTable">
            <thead>
                <tr>
                    <th>ID Reservasi</th>
                    <th>Pelanggan</th>
                    <th>No. HP</th>
                    <th>Mobil</th>
                    <th>Plat</th>
                    <th>Tanggal</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!$data): ?>
                    <tr>
                        <td colspan="8" class="empty-row">
                            Belum ada data reservasi.
                        </td>
                    </tr>
                <?php endif; ?>

                <?php foreach ($data as $r): ?>
                    <tr>
                        <td><?=h($r['id_reservasi'])?></td>
                        <td><?=h($r['nama_penyewa'] ?? '-')?></td>
                        <td><?=h($r['no_hp'] ?? '-')?></td>
                        <td><?=h($r['merk_mobil'] ?? '-')?></td>
                        <td><?=h($r['plat_kendaraan'] ?? '-')?></td>
                        <td><?=h($r['tanggal_reservasi'])?></td>
                        <td>
                            <span class="status success">Disetujui</span>
                        </td>
                        <td>
                            <button
                                type="button"
                                class="action-btn delete-btn reservation-delete-btn"
                                title="Hapus"
                                data-id="<?=h($r['id_reservasi'])?>"
                                data-nama="<?=h($r['nama_penyewa'] ?? '-')?>"
                                data-mobil="<?=h($r['merk_mobil'] ?? '-')?>"
                                data-plat="<?=h($r['plat_kendaraan'] ?? '-')?>"
                            >
                                <i data-lucide="trash-2"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- POPUP KONFIRMASI HAPUS RESERVASI -->
<div class="delete-modal" id="deleteModal">
    <div class="delete-modal-content">

        <div class="delete-modal-icon">
            <i data-lucide="alert-triangle"></i>
        </div>

        <h3>Hapus Data Reservasi?</h3>

        <p class="delete-description">
            Apakah kamu yakin ingin menghapus reservasi berikut?
        </p>

        <div class="delete-car-info">
            <strong id="deleteCarName">Nama Pelanggan</strong>
            <span id="deleteCarPlate">Mobil dan Plat Kendaraan</span>
        </div>

        <div class="delete-warning">
            <i data-lucide="alert-circle"></i>
            <span>Data yang sudah dihapus tidak dapat dikembalikan.</span>
        </div>

        <form method="POST" action="reservasi.php">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="deleteCarId">

            <div class="delete-modal-actions">
                <button type="button" class="cancel-btn" id="cancelDelete">
                    Batal
                </button>

                <button type="submit" class="confirm-delete-btn">
                    <i data-lucide="trash-2"></i>
                    Hapus Reservasi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Pencarian daftar reservasi
document.getElementById('searchTable').addEventListener('input', function() {
    const q = this.value.toLowerCase();

    document.querySelectorAll('#dataTable tbody tr').forEach(row => {
        row.style.display = row.innerText.toLowerCase().includes(q) ? '' : 'none';
    });
});

// Popup konfirmasi hapus reservasi
const deleteModal = document.getElementById('deleteModal');
const deleteId = document.getElementById('deleteCarId');
const deleteName = document.getElementById('deleteCarName');
const deletePlate = document.getElementById('deleteCarPlate');
const cancelDelete = document.getElementById('cancelDelete');

document.querySelectorAll('.reservation-delete-btn').forEach(button => {
    button.addEventListener('click', function() {
        deleteId.value = this.dataset.id;
        deleteName.textContent = this.dataset.nama;
        deletePlate.textContent = this.dataset.mobil + ' - ' + this.dataset.plat;

        deleteModal.classList.add('show');
        document.body.style.overflow = 'hidden';
    });
});

function hideDeleteModal() {
    deleteModal.classList.remove('show');
    document.body.style.overflow = '';
}

cancelDelete.addEventListener('click', hideDeleteModal);

deleteModal.addEventListener('click', function(event) {
    if (event.target === deleteModal) {
        hideDeleteModal();
    }
});
</script>

<?php endPage(); ?>
