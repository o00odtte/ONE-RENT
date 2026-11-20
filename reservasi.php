<?php
require_once 'module_helper.php';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'save') {
            $customer = trim($_POST['id_penyewa'] ?? '');
            $car = trim($_POST['id_mobil'] ?? '');
            $date = trim($_POST['tanggal_reservasi'] ?? '');
            if ($customer === '' || $car === '' || $date === '') throw new Exception('Lengkapi semua data reservasi.');
            if (!isValidDate($date)) throw new Exception('Format tanggal reservasi tidak valid.');
            if ($date !== todayDate()) throw new Exception('Tanggal reservasi harus sesuai dengan tanggal hari ini.');
            if (!oneParam($conn, 'SELECT id_penyewa FROM penyewa WHERE id_penyewa=?', 's', [$customer])) throw new Exception('Pelanggan tidak ditemukan.');
            $carRow = oneParam($conn, 'SELECT id_mobil,status_mobil FROM mobil WHERE id_mobil=?', 's', [$car]);
            if (!$carRow) throw new Exception('Mobil tidak ditemukan.');
            if (strtolower($carRow['status_mobil']) !== 'tersedia') throw new Exception('Mobil tidak tersedia untuk reservasi.');
            $pending = oneParam($conn, "SELECT r.id_reservasi FROM reservasi r LEFT JOIN transaksisewa t ON t.id_reservasi=r.id_reservasi WHERE r.id_mobil=? AND t.id_transaksi IS NULL LIMIT 1", 's', [$car]);
            if ($pending) throw new Exception('Mobil sudah memiliki reservasi yang belum diproses menjadi transaksi.');
            $id = nextId($conn, 'reservasi', 'id_reservasi', 'RSV');
            $admin = $_SESSION['id_admin'] ?? null;
            runStmt($conn, "INSERT INTO reservasi(id_reservasi,id_admin,id_penyewa,id_mobil,tanggal_reservasi,status_reservasi) VALUES(?,?,?,?,?,'disetujui')", 'sssss', [$id,$admin,$customer,$car,$date]);
            flashMessage('success', "Reservasi berhasil ditambahkan dengan ID $id.");
        } elseif ($action === 'delete') {
            $id = trim($_POST['id'] ?? '');
            if ($id === '') throw new Exception('ID reservasi tidak valid.');
            if (!oneParam($conn, 'SELECT id_reservasi FROM reservasi WHERE id_reservasi=?', 's', [$id])) throw new Exception('Reservasi tidak ditemukan.');
            if (oneParam($conn, 'SELECT id_transaksi FROM transaksisewa WHERE id_reservasi=?', 's', [$id])) throw new Exception('Reservasi sudah memiliki transaksi dan tidak dapat dihapus.');
            runStmt($conn, 'DELETE FROM reservasi WHERE id_reservasi=?', 's', [$id]);
            flashMessage('success', 'Data reservasi berhasil dihapus.');
        } else { throw new Exception('Aksi tidak dikenali.'); }
    }
} catch (Throwable $e) { flashMessage('error', $e->getMessage()); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') { header('Location: reservasi.php'); exit; }
$customers = rows($conn, 'SELECT id_penyewa,nama_penyewa,no_hp FROM penyewa ORDER BY nama_penyewa');
$cars = rows($conn, "SELECT m.id_mobil,m.merk_mobil,m.plat_kendaraan,m.tarif_sewa FROM mobil m WHERE LOWER(m.status_mobil)='tersedia' AND NOT EXISTS (SELECT 1 FROM reservasi r LEFT JOIN transaksisewa t ON t.id_reservasi=r.id_reservasi WHERE r.id_mobil=m.id_mobil AND t.id_transaksi IS NULL) ORDER BY m.merk_mobil");
$data = rows($conn, 'SELECT r.*,p.nama_penyewa,p.no_hp,m.merk_mobil,m.plat_kendaraan FROM reservasi r LEFT JOIN penyewa p ON r.id_penyewa=p.id_penyewa LEFT JOIN mobil m ON r.id_mobil=m.id_mobil ORDER BY r.tanggal_reservasi DESC,r.id_reservasi DESC');
beginPage('Reservasi','Kelola reservasi pelanggan ONE RENT.','reservasi');
?>
<div class="module-intro"><div><h2>Data Reservasi</h2><p>Reservasi dicatat untuk pelanggan yang sudah memastikan penyewaan mobil.</p></div></div>
<div class="module-panel module-form-wrap"><h3>Tambah Reservasi</h3><form method="POST" class="module-form"><input type="hidden" name="action" value="save">
<label>Nama Pelanggan<select name="id_penyewa" required><?=optionRows($customers,'id_penyewa','nama_penyewa','Pilih pelanggan')?></select></label>
<label>Mobil<select name="id_mobil" required><option value="">Pilih mobil tersedia</option><?php foreach($cars as $c): ?><option value="<?=h($c['id_mobil'])?>"><?=h($c['merk_mobil'].' - '.$c['plat_kendaraan'].' ('.$c['id_mobil'].')')?></option><?php endforeach; ?></select></label>
<label>Tanggal Reservasi<input type="date" name="tanggal_reservasi" value="<?=h(todayDate())?>" min="<?=h(todayDate())?>" max="<?=h(todayDate())?>" required></label>
<label>Status Reservasi<input value="Disetujui" disabled><small class="muted">Reservasi hanya dicatat untuk pelanggan yang sudah fix menyewa.</small></label>
<div class="form-actions"><button class="primary-btn" type="submit"><i data-lucide="plus"></i>Simpan Reservasi</button></div></form></div>
<div class="module-panel"><div class="module-toolbar"><div><h3>Daftar Reservasi</h3><p class="muted">Total <?=count($data)?> reservasi</p></div><input type="search" id="searchTable" placeholder="Cari pelanggan, mobil, ID..."></div><div class="module-table"><table id="dataTable"><thead><tr><th>ID Reservasi</th><th>Pelanggan</th><th>No. HP</th><th>Mobil</th><th>Plat</th><th>Tanggal</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php if(!$data): ?><tr><td colspan="8" class="empty-row">Belum ada data reservasi.</td></tr><?php endif; ?>
<?php foreach($data as $r): ?><tr><td><?=h($r['id_reservasi'])?></td><td><?=h($r['nama_penyewa']??'-')?></td><td><?=h($r['no_hp']??'-')?></td><td><?=h($r['merk_mobil']??'-')?></td><td><?=h($r['plat_kendaraan']??'-')?></td><td><?=h($r['tanggal_reservasi'])?></td><td><span class="status success">Disetujui</span></td><td><form method="POST" onsubmit="return confirm('Hapus reservasi ini?')" class="inline-actions"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=h($r['id_reservasi'])?>"><button class="del" type="submit">Hapus</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></div><script>document.getElementById('searchTable').addEventListener('input',function(){let q=this.value.toLowerCase();document.querySelectorAll('#dataTable tbody tr').forEach(r=>r.style.display=r.innerText.toLowerCase().includes(q)?'':'none')});</script>
<?php endPage(); ?>
