<?php
// PROFIL ADMIN: mengambil data akun serta memproses perubahan nama dan password admin.
require_once 'module_helper.php';
$adminId = $_SESSION['id_admin'];
$admin = oneParam($conn,'SELECT id_admin,nama_admin,username,password FROM admin WHERE id_admin=?','s',[$adminId]);
if (!$admin) { session_unset(); session_destroy(); header('Location: login.php'); exit; }
try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['nama_admin'] ?? '');
        $current = (string)($_POST['password_lama'] ?? '');
        $new = (string)($_POST['password_baru'] ?? '');
        $confirm = (string)($_POST['konfirmasi_password'] ?? '');
        if ($name === '' || mb_strlen($name) > 60) throw new Exception('Nama admin wajib diisi dan maksimal 60 karakter.');
        if ($new !== '' || $confirm !== '' || $current !== '') {
            if ($current === '') throw new Exception('Masukkan password saat ini untuk mengubah password.');
            $valid = password_verify($current,$admin['password']) || hash_equals((string)$admin['password'],$current);
            if (!$valid) throw new Exception('Password saat ini tidak sesuai.');
            if (strlen($new) < 8) throw new Exception('Password baru minimal 8 karakter.');
            if ($new !== $confirm) throw new Exception('Konfirmasi password baru tidak sesuai.');
            $hash = password_hash($new,PASSWORD_DEFAULT);
            runStmt($conn,'UPDATE admin SET nama_admin=?,password=? WHERE id_admin=?','sss',[$name,$hash,$adminId]);
        } else {
            runStmt($conn,'UPDATE admin SET nama_admin=? WHERE id_admin=?','ss',[$name,$adminId]);
        }
        $_SESSION['nama_admin'] = $name;
        flashMessage('success','Profil admin berhasil diperbarui.');
        header('Location: profil.php'); exit;
    }
} catch (Throwable $e) { flashMessage('error',$e->getMessage()); header('Location: profil.php'); exit; }
$admin = oneParam($conn,'SELECT id_admin,nama_admin,username FROM admin WHERE id_admin=?','s',[$adminId]);
beginPage('Profil Admin','Kelola informasi akun administrator.','profil');
?>
<div class="module-intro"><div><h2>Profil Admin</h2><p>Perbarui nama akun atau ganti password administrator.</p></div></div>
<div class="module-panel" style="max-width:720px"><form method="POST" class="module-form" autocomplete="off">
<label>ID Admin<input value="<?=h($admin['id_admin'])?>" disabled></label>
<label>Username<input value="<?=h($admin['username'])?>" disabled></label>
<label style="grid-column:1/-1">Nama Admin<input type="text" name="nama_admin" maxlength="60" value="<?=h($admin['nama_admin'])?>" required></label>
<div style="grid-column:1/-1;border-top:1px solid var(--border);padding-top:16px"><strong>Ganti Password</strong><p class="muted" style="font-size:11px;margin-top:5px">Kosongkan bagian password jika tidak ingin menggantinya.</p></div>
<label>Password Saat Ini<input type="password" name="password_lama" autocomplete="current-password"></label>
<label>Password Baru<input type="password" name="password_baru" minlength="8" autocomplete="new-password"></label>
<label style="grid-column:1/-1">Konfirmasi Password Baru<input type="password" name="konfirmasi_password" minlength="8" autocomplete="new-password"></label>
<div class="form-actions"><button class="primary-btn" type="submit"><i data-lucide="save"></i>Simpan Perubahan</button></div>
</form></div>
<?php endPage(); ?>
