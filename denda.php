<?php
require_once 'module_helper.php';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (($_POST['action'] ?? '') !== 'save') throw new Exception('Aksi denda tidak dikenali.');
        $kid = trim($_POST['id_pengembalian'] ?? ''); $date = trim($_POST['tanggal_denda'] ?? '');
        $jenis = trim($_POST['jenis_denda'] ?? ''); $note = trim($_POST['keterangan'] ?? ''); $amountRaw = trim((string)($_POST['jumlah_denda'] ?? ''));
        if ($kid === '' || $date === '' || $jenis === '' || $amountRaw === '') throw new Exception('Lengkapi data denda.');
        if (!isValidDate($date)) throw new Exception('Format tanggal denda tidak valid.');
        if ($date > todayDate()) throw new Exception('Tanggal denda tidak boleh melebihi hari ini.');
        if (mb_strlen($jenis) > 20) throw new Exception('Jenis denda maksimal 20 karakter.');
        if (!is_numeric($amountRaw) || !is_finite((float)$amountRaw) || (float)$amountRaw <= 0 || (float)$amountRaw > 99999999.99) throw new Exception('Jumlah denda harus berupa angka lebih dari nol dan tidak melebihi batas sistem.');
        $ret = oneParam($conn,'SELECT id_pengembalian,tanggal_kembali FROM pengembalian WHERE id_pengembalian=?','s',[$kid]);
        if (!$ret) throw new Exception('Data pengembalian tidak ditemukan.');
        if ($date < $ret['tanggal_kembali']) throw new Exception('Tanggal denda tidak boleh sebelum tanggal pengembalian ('.$ret['tanggal_kembali'].').');
        $id = nextId($conn,'denda','id_denda','DND'); $amount = (float)$amountRaw;
        runStmt($conn,'INSERT INTO denda(id_denda,id_pengembalian,tanggal_denda,jenis_denda,keterangan,jumlah_denda) VALUES(?,?,?,?,?,?)','sssssd',[$id,$kid,$date,$jenis,$note,$amount]);
        flashMessage('success',"Denda $id berhasil dicatat. Jika sudah dibayar, catat juga sebagai pembayaran jenis DENDA di halaman Pembayaran.");
    }
} catch (Throwable $e) { flashMessage('error',$e->getMessage()); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') { header('Location: denda.php'); exit; }
$returns = rows($conn,'SELECT k.id_pengembalian,k.tanggal_kembali,p.nama_penyewa,m.merk_mobil FROM pengembalian k JOIN transaksisewa t ON k.id_transaksi=t.id_transaksi JOIN reservasi r ON t.id_reservasi=r.id_reservasi JOIN penyewa p ON r.id_penyewa=p.id_penyewa JOIN mobil m ON r.id_mobil=m.id_mobil ORDER BY k.tanggal_kembali DESC');
$data = rows($conn,'SELECT d.*,k.id_transaksi,p.nama_penyewa,m.merk_mobil FROM denda d JOIN pengembalian k ON d.id_pengembalian=k.id_pengembalian JOIN transaksisewa t ON k.id_transaksi=t.id_transaksi JOIN reservasi r ON t.id_reservasi=r.id_reservasi JOIN penyewa p ON r.id_penyewa=p.id_penyewa JOIN mobil m ON r.id_mobil=m.id_mobil ORDER BY d.tanggal_denda DESC');
beginPage('Denda','Kelola denda keterlambatan atau kerusakan kendaraan.','denda');
?>
<div class="module-intro"><div><h2>Data Denda</h2><p>Catat denda jika ada keterlambatan atau kondisi kendaraan yang memerlukan biaya tambahan.</p></div></div>
<div class="module-panel module-form-wrap"><h3>Tambah Denda</h3><form method="POST" class="module-form"><input type="hidden" name="action" value="save"><label>Data Pengembalian<select name="id_pengembalian" required><option value="">Pilih pengembalian</option><?php foreach($returns as $r): ?><option value="<?=h($r['id_pengembalian'])?>"><?=h($r['id_pengembalian'].' - '.$r['nama_penyewa'].' - '.$r['merk_mobil'])?></option><?php endforeach; ?></select></label><label>Jenis Denda<input name="jenis_denda" maxlength="20" placeholder="Contoh: Keterlambatan" required></label><label>Tanggal Denda<input type="date" name="tanggal_denda" value="<?=h(todayDate())?>" max="<?=h(todayDate())?>" required></label><label>Jumlah Denda (Rp)<input type="number" name="jumlah_denda" min="0.01" max="99999999.99" step="0.01" required></label><label style="grid-column:1/-1">Keterangan<textarea name="keterangan" placeholder="Keterangan tambahan (opsional)"></textarea></label><div class="form-actions"><button class="primary-btn" type="submit"><i data-lucide="plus"></i>Simpan Denda</button></div></form></div>
<div class="module-panel"><div class="module-toolbar"><div><h3>Daftar Denda</h3><p class="muted">Total <?=count($data)?> denda</p></div><input id="searchTable" type="search" placeholder="Cari denda, pelanggan..."></div><div class="module-table"><table id="dataTable"><thead><tr><th>ID Denda</th><th>ID Pengembalian</th><th>Transaksi</th><th>Pelanggan</th><th>Mobil</th><th>Tanggal</th><th>Jenis</th><th>Jumlah</th><th>Keterangan</th></tr></thead><tbody><?php if(!$data): ?><tr><td colspan="9" class="empty-row">Belum ada denda.</td></tr><?php endif; ?><?php foreach($data as $r): ?><tr><td><?=h($r['id_denda'])?></td><td><?=h($r['id_pengembalian'])?></td><td><?=h($r['id_transaksi'])?></td><td><?=h($r['nama_penyewa'])?></td><td><?=h($r['merk_mobil'])?></td><td><?=h($r['tanggal_denda'])?></td><td><?=h($r['jenis_denda'])?></td><td>Rp <?=number_format((float)$r['jumlah_denda'],0,',','.')?></td><td><?=h($r['keterangan'])?></td></tr><?php endforeach; ?></tbody></table></div></div>
<script>document.getElementById('searchTable').addEventListener('input',function(){let q=this.value.toLowerCase();document.querySelectorAll('#dataTable tbody tr').forEach(r=>r.style.display=r.innerText.toLowerCase().includes(q)?'':'none')});</script>
<?php endPage(); ?>
