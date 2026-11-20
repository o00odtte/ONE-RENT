ONE RENT - Aplikasi Admin Rental Mobil

ISI FOLDER
- login.php / logout.php / auth.php: autentikasi admin dan proteksi halaman
- dashboard.php: ringkasan seluruh data
- mobil.php: tambah, edit, hapus, dan pencarian mobil
- pelanggan.php: tambah, edit, hapus, dan pencarian pelanggan
- reservasi.php: reservasi pelanggan dan mobil
- transaksi.php: transaksi sewa dan perhitungan harga
- pembayaran.php: pelunasan dan pembayaran denda
- pengembalian.php: pencatatan pengembalian dan hitung keterlambatan
- denda.php: pencatatan denda
- profil.php: ubah nama admin dan password
- module_helper.php: fungsi bersama, tanggal, notifikasi, dan sidebar
- dbUAS.sql: dump database yang disertakan dalam project awal

PERSIAPAN
1. Backup folder project dan database sebelum mengganti file.
2. Letakkan folder rental-mobil-ui di dalam htdocs XAMPP.
3. Pastikan database mbdminiproject sudah di-import melalui phpMyAdmin.
4. Periksa host, username, password, dan nama database pada koneksi.php agar sesuai dengan MySQL lokal kamu.
5. Jalankan Apache dan MySQL, lalu buka http://localhost/rental-mobil-ui/.
6. Halaman awal otomatis diarahkan ke login.php.

ATURAN VALIDASI TANGGAL
- Tanggal lahir pelanggan harus tanggal kalender yang valid dan tidak boleh di masa depan.
- Tanggal reservasi wajib sama dengan tanggal hari ini (zona waktu Asia/Jakarta). Tanggal kemarin atau besok ditolak.
- Tanggal mulai sewa tidak boleh sebelum hari ini. Tanggal selesai harus sama atau setelah tanggal mulai.
- Tanggal pembayaran pelunasan boleh dilakukan sejak tanggal reservasi (termasuk sebelum tanggal mulai sewa), tetapi tidak boleh sebelum tanggal reservasi atau setelah hari ini. Pembayaran denda mengikuti tanggal pengembalian dan tanggal denda.
- Tanggal kembali tidak boleh sebelum tanggal mulai sewa atau setelah hari ini. Pengembalian boleh dicatat lebih awal atau terlambat sesuai tanggal aktual; keterlambatan dihitung dari tanggal selesai sewa.
- Tanggal denda tidak boleh sebelum tanggal pengembalian atau setelah hari ini.
- Dashboard dan halaman admin menampilkan peringatan untuk transaksi yang melewati tanggal selesai sewa tetapi belum memiliki catatan pengembalian.
- Validasi dilakukan di server, bukan hanya mengandalkan batas tanggal pada form.

ATURAN TRANSAKSI
- Satu reservasi hanya dapat memiliki satu transaksi sewa.
- Mobil yang sudah memiliki reservasi belum diproses tidak dapat dipesan ulang.
- Harga sewa dihitung inklusif: tanggal mulai dan tanggal selesai ikut dihitung.
- Satu transaksi hanya boleh memiliki satu pembayaran PELUNASAN dengan jumlah sama dengan total sewa.
- Pembayaran DENDA hanya dapat dicatat jika denda sudah ada, satu kali per transaksi, dan jumlahnya harus sama dengan akumulasi denda.
- Satu transaksi hanya boleh memiliki satu pengembalian.
- Data induk yang masih dipakai oleh data lain tidak dapat dihapus karena relasi database.
- Status mobil aktif disinkronkan dengan transaksi dan pengembalian; mobil Perawatan tidak diubah menjadi Disewa oleh sinkronisasi.

URUTAN PENGUJIAN YANG DISARANKAN
1. Login dan buka Dashboard.
2. Tambah/edit/hapus mobil dan pelanggan (uji juga nomor polisi, KTP, SIM A duplikat).
3. Buat reservasi dengan tanggal hari ini; uji tanggal kemarin dan besok harus ditolak.
4. Buat transaksi; uji tanggal mulai kemarin dan tanggal selesai sebelum mulai harus ditolak.
5. Catat pelunasan sesuai total sewa; uji nominal salah, pembayaran ganda, tanggal sebelum mulai, dan tanggal masa depan.
6. Catat pengembalian; uji tanggal sebelum mulai dan tanggal masa depan.
7. Jika ada denda, catat denda lalu pembayaran DENDA sesuai total akumulasi.
8. Uji Profil Admin dan Logout.
9. Periksa bahwa notifikasi sukses/gagal menghilang otomatis setelah sekitar 3 detik.

CATATAN PENTING
- File PHP telah diperiksa dengan php -l. Pemeriksaan sintaks bukan pengganti pengujian langsung pada XAMPP dengan database kamu.
- Jangan mengimpor ulang dbUAS.sql ke database yang sudah berisi data penting karena dump SQL dapat menghapus/membuat ulang tabel. Backup database terlebih dahulu.
- Password admin lama berbentuk teks biasa akan otomatis di-hash setelah login berhasil. Password baru yang dibuat melalui pendaftaran/profil disimpan dalam bentuk hash.


PERBAIKAN STATUS RESERVASI (PENTING)
Jika database sudah pernah di-import sebelum versi ini, buka phpMyAdmin > database mbdminiproject > tab SQL, lalu jalankan isi file perbaikan_reservasi.sql satu kali. File ini menghapus trigger lama yang mengubah status mobil menjadi Disewa saat reservasi dibuat. Setelah itu, status Disewa baru diberikan ketika transaksi sewa dibuat. Jangan import ulang dbUAS.sql jika database sudah berisi data.
