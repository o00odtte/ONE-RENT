-- Jalankan SATU KALI pada database mbdminiproject yang sudah ada.
-- Menghapus trigger lama yang keliru mengubah status mobil menjadi Disewa
-- segera setelah reservasi dibuat.
DROP TRIGGER IF EXISTS trg_status_mobil;

-- Sinkronkan status mobil berdasarkan transaksi yang benar-benar aktif.
-- Mobil Perawatan tidak disentuh. Reservasi tanpa transaksi tetap Tersedia,
-- tetapi tidak bisa dipesan ulang karena sudah memiliki reservasi pending.
UPDATE mobil m
SET m.status_mobil = CASE
    WHEN EXISTS (
        SELECT 1
        FROM reservasi r
        INNER JOIN transaksisewa t ON t.id_reservasi = r.id_reservasi
        LEFT JOIN pengembalian k ON k.id_transaksi = t.id_transaksi
        WHERE r.id_mobil = m.id_mobil
          AND k.id_pengembalian IS NULL
    ) THEN 'Disewa'
    ELSE 'Tersedia'
END
WHERE LOWER(m.status_mobil) <> 'perawatan';
