
<?php
// DASHBOARD: menyiapkan ringkasan jumlah data dan kartu navigasi utama ONE RENT.

require_once 'module_helper.php';


function totalDataDashboard($conn, $table)
{
    $allowed = [
        'mobil',
        'penyewa',
        'reservasi',
        'transaksisewa',
        'pengembalian',
        'pembayaran',
        'denda'
    ];

    if (!in_array($table, $allowed, true)) {
        return 0;
    }

    $result = mysqli_query($conn, "SELECT COUNT(*) AS total FROM `$table`");

    return $result
        ? (int)mysqli_fetch_assoc($result)['total']
        : 0;
}


$nama = $_SESSION['nama_admin'] ?? 'Administrator';

$cards = [
    ['Data Mobil', 'mobil', 'mobil.php', 'car-front', 'Jumlah seluruh mobil terdaftar'],
    ['Data Pelanggan', 'penyewa', 'pelanggan.php', 'users', 'Jumlah pelanggan terdaftar'],
    ['Reservasi', 'reservasi', 'reservasi.php', 'calendar-check', 'Data reservasi pelanggan'],
    ['Transaksi Sewa', 'transaksisewa', 'transaksi.php', 'clipboard-list', 'Transaksi penyewaan tercatat'],
    ['Pembayaran', 'pembayaran', 'pembayaran.php', 'wallet', 'Riwayat pembayaran tercatat'],
    ['Pengembalian', 'pengembalian', 'pengembalian.php', 'undo-2', 'Mobil yang sudah dikembalikan'],
    ['Denda', 'denda', 'denda.php', 'receipt', 'Denda yang tercatat']
];


beginPage(
    'Dashboard',
    'Ringkasan pengelolaan rental mobil ONE RENT.',
    'dashboard'
);
?>

<style>
.dashboard-welcome {
    background: linear-gradient(110deg, #2D3250, #424769);
    border: 1px solid rgba(255,255,255,.08);
    border-radius: 12px;
    padding: 24px 26px;
    margin-bottom: 24px;
    color: #fff;
}

.dashboard-welcome h2 {
    font-size: 20px;
    margin: 0 0 7px;
    font-weight: 600;
}

.dashboard-welcome p {
    font-size: 12px;
    color: #d7d9e5;
    margin: 0;
}

.dashboard-section-title {
    font-size: 16px;
    font-weight: 600;
    margin: 0 0 15px;
}

.dashboard-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
}

.dashboard-card {
    display: block;
    text-decoration: none;
    color: #fff;
    background: var(--card);
    border: 1px solid var(--border);
    border-radius: 11px;
    padding: 19px;
    transition: .2s;
}

.dashboard-card:hover {
    transform: translateY(-2px);
    border-color: var(--secondary);
}

.dashboard-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.dashboard-card-icon {
    width: 38px;
    height: 38px;
    border-radius: 9px;
    background: rgba(249,177,122,.13);
    color: var(--peach);
    display: flex;
    align-items: center;
    justify-content: center;
}

.dashboard-card-number {
    font-size: 27px;
    font-weight: 700;
    margin: 17px 0 3px;
}

.dashboard-card-label {
    font-size: 13px;
    font-weight: 600;
}

.dashboard-card-desc {
    font-size: 11px;
    color: var(--muted);
    margin-top: 5px;
}

.dashboard-card-link {
    font-size: 11px;
    color: var(--peach);
    margin-top: 16px;
    display: flex;
    align-items: center;
    gap: 5px;
}

@media(max-width:1000px) {
    .dashboard-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media(max-width:600px) {
    .dashboard-grid {
        grid-template-columns: 1fr;
    }

    .dashboard-welcome {
        padding: 20px;
    }
}
</style>

<div class="dashboard-welcome">
    <h2>Selamat datang, <?=h($nama)?>!</h2>

    <p>
        Kelola data mobil, pelanggan, reservasi, transaksi, pembayaran,
        pengembalian, dan denda melalui ONE RENT.
    </p>
</div>

<h3 class="dashboard-section-title">Ringkasan Data</h3>

<div class="dashboard-grid">

    <?php foreach ($cards as $card): ?>

        <a class="dashboard-card" href="<?=h($card[2])?>">

            <div class="dashboard-card-top">

                <span class="dashboard-card-label">
                    <?=h($card[0])?>
                </span>

                <span class="dashboard-card-icon">
                    <i data-lucide="<?=h($card[3])?>"></i>
                </span>

            </div>

            <div class="dashboard-card-number">
                <?=number_format(totalDataDashboard($conn, $card[1]))?>
            </div>

            <div class="dashboard-card-desc">
                <?=h($card[4])?>
            </div>

            <div class="dashboard-card-link">
                Lihat data
                <i data-lucide="arrow-right"></i>
            </div>

        </a>

    <?php endforeach; ?>

</div>

<?php endPage(); ?>
