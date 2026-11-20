<?php
require_once 'module_helper.php';
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        if ($action === 'save') {
            $rid = trim($_POST['id_reservasi'] ?? ''); $start = trim($_POST['tanggal_mulai'] ?? ''); $end = trim($_POST['tanggal_selesai'] ?? '');
            if ($rid === '' || $start === '' || $end === '') throw new Exception('Lengkapi data transaksi.');
            if (!isValidDate($start) || !isValidDate($end)) throw new Exception('Format tanggal sewa tidak valid.');
            if ($start < todayDate()) throw new Exception('Tanggal mulai sewa tidak boleh sebelum hari ini.');
            if ($end < $start) throw new Exception('Tanggal selesai tidak boleh sebelum tanggal mulai.');
            $res = oneParam($conn, 'SELECT r.id_reservasi,r.id_mobil,m.tarif_sewa,m.status_mobil FROM reservasi r JOIN mobil m ON r.id_mobil=m.id_mobil WHERE r.id_reservasi=?', 's', [$rid]);
            if (!$res) throw new Exception('Reservasi tidak ditemukan.');
            if (oneParam($conn, 'SELECT id_transaksi FROM transaksisewa WHERE id_reservasi=?', 's', [$rid])) throw new Exception('Reservasi ini sudah memiliki transaksi sewa.');
            if (strtolower($res['status_mobil']) !== 'tersedia') throw new Exception('Mobil tidak lagi tersedia untuk transaksi ini.');
            $days = (int)((strtotime($end)-strtotime($start))/86400)+1;
            $total = $days * (float)$res['tarif_sewa'];
            if ($total <= 0 || $total > 99999999.99) throw new Exception('Total harga sewa berada di luar batas yang diizinkan.');
            $id = nextId($conn,'transaksisewa','id_transaksi','TRX');
            mysqli_begin_transaction($conn);
            try {
                runStmt($conn,'INSERT INTO transaksisewa(id_transaksi,id_reservasi,tanggal_mulai,tanggal_selesai,total_harga) VALUES(?,?,?,?,?)','ssssd',[$id,$rid,$start,$end,$total]);
                runStmt($conn,"UPDATE mobil SET status_mobil='Disewa' WHERE id_mobil=?",'s',[$res['id_mobil']]);
                mysqli_commit($conn);
            } catch (Throwable $e) { mysqli_rollback($conn); throw $e; }
            flashMessage('success',"Transaksi $id berhasil dibuat. Durasi $days hari, total Rp ".number_format($total,0,',','.').'.');
        } elseif ($action === 'delete') {
            $id = trim($_POST['id'] ?? ''); if ($id === '') throw new Exception('ID transaksi tidak valid.');
            $tr = oneParam($conn,'SELECT t.id_transaksi,r.id_mobil FROM transaksisewa t JOIN reservasi r ON t.id_reservasi=r.id_reservasi WHERE t.id_transaksi=?','s',[$id]);
            if (!$tr) throw new Exception('Transaksi tidak ditemukan.');
            if (oneParam($conn,'SELECT id_pembayaran FROM pembayaran WHERE id_transaksi=? LIMIT 1','s',[$id]) || oneParam($conn,'SELECT id_pengembalian FROM pengembalian WHERE id_transaksi=? LIMIT 1','s',[$id])) throw new Exception('Transaksi sudah memiliki pembayaran atau pengembalian sehingga tidak dapat dihapus.');
            mysqli_begin_transaction($conn);
            try { runStmt($conn,'DELETE FROM transaksisewa WHERE id_transaksi=?','s',[$id]); runStmt($conn,"UPDATE mobil SET status_mobil='Tersedia' WHERE id_mobil=? AND status_mobil='Disewa'",'s',[$tr['id_mobil']]); mysqli_commit($conn); }
            catch(Throwable $e) { mysqli_rollback($conn); throw $e; }
            flashMessage('success','Transaksi berhasil dihapus.');
        } else { throw new Exception('Aksi tidak dikenali.'); }
    }
} catch (Throwable $e) { flashMessage('error',$e->getMessage()); }
if ($_SERVER['REQUEST_METHOD'] === 'POST') { header('Location: transaksi.php'); exit; }
$reservations = rows($conn,"SELECT r.id_reservasi,p.nama_penyewa,m.merk_mobil,m.plat_kendaraan,m.tarif_sewa FROM reservasi r JOIN penyewa p ON r.id_penyewa=p.id_penyewa JOIN mobil m ON r.id_mobil=m.id_mobil LEFT JOIN transaksisewa t ON r.id_reservasi=t.id_reservasi WHERE t.id_transaksi IS NULL AND LOWER(m.status_mobil)='tersedia' ORDER BY r.tanggal_reservasi DESC");
$data = rows($conn,'SELECT t.*,r.id_reservasi,p.nama_penyewa,m.merk_mobil,m.plat_kendaraan FROM transaksisewa t JOIN reservasi r ON t.id_reservasi=r.id_reservasi JOIN penyewa p ON r.id_penyewa=p.id_penyewa JOIN mobil m ON r.id_mobil=m.id_mobil ORDER BY t.tanggal_mulai DESC');
beginPage('Transaksi Sewa','Kelola transaksi penyewaan mobil ONE RENT.','transaksi');
?>
<div class="module-intro"><div><h2>Data Transaksi Sewa</h2><p>Buat transaksi dari reservasi yang sudah disetujui.</p></div></div>
<div class="module-panel module-form-wrap"><h3>Tambah Transaksi Sewa</h3><form method="POST" class="module-form"><input type="hidden" name="action" value="save">
<label>Reservasi<select name="id_reservasi" required><option value="">Pilih reservasi</option><?php foreach($reservations as $r): ?><option value="<?=h($r['id_reservasi'])?>" data-tarif="<?=h($r['tarif_sewa'])?>"><?=h($r['id_reservasi'].' - '.$r['nama_penyewa'].' - '.$r['merk_mobil'].' ('.$r['plat_kendaraan'].')')?></option><?php endforeach; ?></select></label>
<label>Tanggal Mulai<input type="date" name="tanggal_mulai" min="<?=h(todayDate())?>" required></label><label>Tanggal Selesai<input type="date" name="tanggal_selesai" min="<?=h(todayDate())?>" required></label>
<div style="grid-column:1/-1;background:var(--navy);border:1px solid var(--border);border-radius:8px;padding:14px 16px"><div class="muted" style="font-size:11px">Perkiraan total harga sewa</div><strong id="priceTotal" style="display:block;font-size:20px;color:var(--peach);margin-top:5px">Rp 0</strong><small id="priceDetail" class="muted">Pilih reservasi dan tanggal sewa untuk menghitung total.</small></div>
<div class="form-actions"><button class="primary-btn" type="submit"><i data-lucide="plus"></i>Simpan Transaksi</button></div></form><p class="muted" style="font-size:11px;margin-top:12px">Perhitungan menggunakan jumlah hari kalender, termasuk tanggal mulai dan selesai.</p></div>
<div class="module-panel"><div class="module-toolbar"><div><h3>Daftar Transaksi</h3><p class="muted">Total <?=count($data)?> transaksi</p></div><input id="searchTable" type="search" placeholder="Cari transaksi, pelanggan, mobil..."></div><div class="module-table"><table id="dataTable"><thead><tr><th>ID Transaksi</th><th>ID Reservasi</th><th>Pelanggan</th><th>Mobil</th><th>Mulai</th><th>Selesai</th><th>Total Harga</th><th>Aksi</th></tr></thead><tbody><?php if(!$data): ?><tr><td colspan="8" class="empty-row">Belum ada transaksi.</td></tr><?php endif; ?><?php foreach($data as $r): ?><tr><td><?=h($r['id_transaksi'])?></td><td><?=h($r['id_reservasi'])?></td><td><?=h($r['nama_penyewa'])?></td><td><?=h($r['merk_mobil'].' / '.$r['plat_kendaraan'])?></td><td><?=h($r['tanggal_mulai'])?></td><td><?=h($r['tanggal_selesai'])?></td><td>Rp <?=number_format((float)$r['total_harga'],0,',','.')?></td><td><form method="POST" onsubmit="return confirm('Hapus transaksi ini?')" class="inline-actions"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?=h($r['id_transaksi'])?>"><button class="del" type="submit">Hapus</button></form></td></tr><?php endforeach; ?></tbody></table></div></div>
<script>
const sf=document.querySelector('.module-form');if(sf){const r=sf.querySelector('[name="id_reservasi"]'),s=sf.querySelector('[name="tanggal_mulai"]'),e=sf.querySelector('[name="tanggal_selesai"]'),out=document.getElementById('priceTotal'),det=document.getElementById('priceDetail');const rp=n=>'Rp '+Number(n||0).toLocaleString('id-ID',{maximumFractionDigits:0});function calc(){const rate=Number(r.options[r.selectedIndex]?.dataset.tarif||0);if(!r.value||!s.value||!e.value){out.textContent='Rp 0';det.textContent='Pilih reservasi dan tanggal sewa untuk menghitung total.';return;}const days=Math.floor((new Date(e.value+'T00:00:00')-new Date(s.value+'T00:00:00'))/86400000)+1;if(days<=0){out.textContent='Tanggal tidak valid';det.textContent='Tanggal selesai harus sama atau setelah tanggal mulai.';return;}out.textContent=rp(rate*days);det.textContent=days+' hari × '+rp(rate)+' per hari';}r.addEventListener('change',calc);s.addEventListener('change',()=>{e.min=s.value;calc()});e.addEventListener('change',calc);}
const search=document.getElementById('searchTable');search.addEventListener('input',function(){let q=this.value.toLowerCase();document.querySelectorAll('#dataTable tbody tr').forEach(x=>x.style.display=x.innerText.toLowerCase().includes(q)?'':'none')});
</script>
<?php endPage(); ?>
