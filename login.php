<?php
// HALAMAN LOGIN: menangani autentikasi admin serta menampilkan form masuk/pendaftaran.

session_start();
require_once 'koneksi.php';

// Jika admin sudah login, langsung ke dashboard.
if (isset($_SESSION['id_admin'])) {
    header("Location: dashboard.php");
    exit;
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$activePanel = 'login';
$error = '';
$success = '';
$loginUsername = '';
$registerName = '';
$registerUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? 'login';

    // =========================
    // PROSES SIGN UP
    // =========================
    if ($action === 'register') {

        $activePanel = 'register';

        $registerName = trim($_POST['nama_admin'] ?? '');
        $registerUsername = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (
            $registerName === '' ||
            $registerUsername === '' ||
            $password === '' ||
            $confirmPassword === ''
        ) {
            $error = 'Semua kolom wajib diisi.';

        } elseif (mb_strlen($registerName) > 60) {
            $error = 'Nama lengkap maksimal 60 karakter.';

        } elseif (strlen($registerUsername) > 15) {
            $error = 'Username maksimal 15 karakter.';

        } elseif (!preg_match('/^[a-zA-Z0-9_.-]+$/', $registerUsername)) {
            $error = 'Username hanya boleh berisi huruf, angka, titik, atau garis bawah.';

        } elseif (strlen($password) < 8) {
            $error = 'Password minimal 8 karakter.';

        } elseif ($password !== $confirmPassword) {
            $error = 'Konfirmasi password tidak sesuai.';

        } else {

            // Periksa apakah username sudah digunakan.
            $check = mysqli_prepare(
                $conn,
                "SELECT id_admin FROM admin WHERE username = ? LIMIT 1"
            );

            mysqli_stmt_bind_param($check, "s", $registerUsername);
            mysqli_stmt_execute($check);
            $resultCheck = mysqli_stmt_get_result($check);

            if (mysqli_num_rows($resultCheck) > 0) {

                $error = 'Username sudah digunakan. Silakan pilih username lain.';

            } else {

                // Buat ID admin otomatis: ADM001, ADM002, dst.
                $query_id = mysqli_query($conn, "
                    SELECT id_admin FROM admin
                    ORDER BY CAST(SUBSTRING(id_admin, 4) AS UNSIGNED) DESC
                    LIMIT 1
                ");

                if (!$query_id) {
                    $error = 'Gagal membuat ID admin. Periksa database.';
                } else {

                    $data_id = mysqli_fetch_assoc($query_id);

                    if ($data_id) {
                        $nomor_terakhir = (int) substr($data_id['id_admin'], 3);
                        $nomor_baru = $nomor_terakhir + 1;
                    } else {
                        $nomor_baru = 1;
                    }

                    $id_admin = 'ADM' . str_pad(
                        $nomor_baru,
                        3,
                        '0',
                        STR_PAD_LEFT
                    );

                    // Password disimpan dalam bentuk hash.
                    $hashedPassword = password_hash(
                        $password,
                        PASSWORD_DEFAULT
                    );

                    // Simpan akun admin.
                    $insert = mysqli_prepare(
                        $conn,
                        "INSERT INTO admin
                        (id_admin, nama_admin, username, password)
                        VALUES (?, ?, ?, ?)"
                    );

                    mysqli_stmt_bind_param(
                        $insert,
                        "ssss",
                        $id_admin,
                        $registerName,
                        $registerUsername,
                        $hashedPassword
                    );

                    if (mysqli_stmt_execute($insert)) {
                        $activePanel = 'login';
                        $success = 'Akun berhasil dibuat. Silakan sign in.';
                        $loginUsername = $registerUsername;
                    } else {
                        $error = 'Pendaftaran gagal. Silakan coba kembali.';
                    }

                    mysqli_stmt_close($insert);
                }
            }

            mysqli_stmt_close($check);
        }

    // =========================
    // PROSES SIGN IN
    // =========================
    } else {

        $activePanel = 'login';

        $loginUsername = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($loginUsername === '' || $password === '') {

            $error = 'Username dan password wajib diisi.';

        } else {

            $stmt = mysqli_prepare(
                $conn,
                "SELECT id_admin, nama_admin, username, password
                 FROM admin
                 WHERE username = ?
                 LIMIT 1"
            );

            mysqli_stmt_bind_param($stmt, "s", $loginUsername);
            mysqli_stmt_execute($stmt);

            $result = mysqli_stmt_get_result($stmt);
            $admin = mysqli_fetch_assoc($result);

            // Mendukung password lama dan password yang sudah di-hash.
            $validPassword = false;

            if ($admin) {
                $validPassword =
                    password_verify($password, $admin['password']) ||
                    hash_equals((string)$admin['password'], (string)$password);
            }

            if ($validPassword) {

                // Jika akun masih memakai password lama berbentuk teks biasa,
                // ubah otomatis menjadi hash setelah login berhasil.
                if (!password_verify($password, $admin['password'])) {
                    $newHash = password_hash($password, PASSWORD_DEFAULT);
                    $upgrade = mysqli_prepare($conn, "UPDATE admin SET password=? WHERE id_admin=?");
                    if ($upgrade) {
                        mysqli_stmt_bind_param($upgrade, "ss", $newHash, $admin['id_admin']);
                        mysqli_stmt_execute($upgrade);
                        mysqli_stmt_close($upgrade);
                    }
                }

                session_regenerate_id(true);

                $_SESSION['id_admin'] = $admin['id_admin'];
                $_SESSION['nama_admin'] = $admin['nama_admin'];
                $_SESSION['username'] = $admin['username'];

                header("Location: dashboard.php");
                exit;

            } else {
                $error = 'Username atau password yang kamu masukkan salah.';
            }

            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login Admin | ONE RENT</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --navy: #2D3250;
            --card: #424769;
            --secondary: #676F9D;
            --peach: #F9B17A;
            --white: #FFFFFF;
            --muted: #A3A9C5;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', sans-serif;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
            background:
                radial-gradient(circle at 12% 15%, rgba(103,111,157,.55), transparent 27%),
                radial-gradient(circle at 90% 85%, rgba(249,177,122,.20), transparent 25%),
                radial-gradient(circle at 80% 10%, rgba(66,71,105,.8), transparent 30%),
                #20253E;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: "";
            position: fixed;
            inset: 0;
            pointer-events: none;
            background-image:
                linear-gradient(rgba(255,255,255,.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,255,255,.025) 1px, transparent 1px);
            background-size: 42px 42px;
        }

        body::after {
            content: "";
            position: fixed;
            width: 300px;
            height: 300px;
            border: 1px solid rgba(249,177,122,.18);
            border-radius: 50%;
            top: -150px;
            right: -80px;
            pointer-events: none;
        }

        .auth-card {
            position: relative;
            width: 100%;
            max-width: 980px;
            height: 555px;
            background: var(--navy);
            border-radius: 17px;
            overflow: hidden;
            box-shadow:
                0 30px 70px rgba(0,0,0,.35),
                0 0 0 1px rgba(255,255,255,.08);
        }

        .form-side {
            position: absolute;
            top: 0;
            left: 0;
            width: 50%;
            height: 100%;
            z-index: 2;
            background: var(--navy);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 55px;
            transition: transform .65s cubic-bezier(.65,0,.35,1);
        }

        .form-content {
            width: 100%;
            max-width: 350px;
        }

        .form-panel {
            display: none;
            animation: appear .4s ease;
        }

        .form-panel.active {
            display: block;
        }

        @keyframes appear {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-panel h1 {
            font-size: 25px;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 5px;
        }

        .form-description {
            color: var(--muted);
            font-size: 11px;
            margin-bottom: 27px;
        }

        .field {
            margin-bottom: 15px;
        }

        .field label {
            display: block;
            color: #C6C9D9;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .5px;
            margin-bottom: 6px;
        }

        .field input {
            width: 100%;
            height: 40px;
            padding: 0 13px;
            background: var(--card);
            border: 1px solid rgba(255,255,255,.05);
            border-radius: 6px;
            color: var(--white);
            font-family: inherit;
            font-size: 11px;
            outline: none;
            transition: .25s;
        }

        .field input::placeholder {
            color: #A3A9C5;
        }

        .field input:focus {
            border-color: var(--peach);
            box-shadow: 0 0 0 2px rgba(249,177,122,.12);
        }

        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper input {
            padding-right: 43px;
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: transparent;
            color: #A3A9C5;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 3px;
        }

        .toggle-password:hover {
            color: var(--peach);
        }

        .toggle-password svg {
            width: 17px;
            height: 17px;
        }

        .submit-btn {
            width: 100%;
            height: 39px;
            border: none;
            border-radius: 6px;
            background: var(--peach);
            color: var(--navy);
            font-family: inherit;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
            margin-top: 5px;
            transition: .25s;
        }

        .submit-btn:hover {
            background: #F6A568;
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(249,177,122,.2);
        }

        .switch-text {
            color: var(--muted);
            font-size: 10px;
            margin-top: 20px;
        }

        .switch-text button {
            border: none;
            background: none;
            color: var(--peach);
            font-family: inherit;
            font-size: 10px;
            font-weight: 800;
            cursor: pointer;
        }

        .switch-text button:hover {
            text-decoration: underline;
        }

        .form-footer {
            color: #8289A8;
            font-size: 9px;
            margin-top: 24px;
        }

        .message {
            padding: 10px 12px;
            border-radius: 6px;
            font-size: 10px;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .error {
            background: rgba(220,53,69,.13);
            border: 1px solid rgba(220,53,69,.4);
            color: #FFB8BE;
        }

        .success {
            background: rgba(40,167,69,.13);
            border: 1px solid rgba(40,167,69,.4);
            color: #A8E6B7;
        }

        .brand-side {
            position: absolute;
            top: 0;
            right: 0;
            width: 50%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: left;
            padding: 55px;
            color: var(--white);
            background:
                radial-gradient(circle at 85% 80%, rgba(249,177,122,.48), transparent 31%),
                linear-gradient(135deg, #676F9D, #424769 75%);
            transition: transform .65s cubic-bezier(.65,0,.35,1);
            overflow: hidden;
        }

        .brand-side::before {
            content: "";
            position: absolute;
            width: 310px;
            height: 310px;
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 50%;
            right: -170px;
            top: -160px;
        }

        .brand-side::after {
            content: "";
            position: absolute;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: rgba(249,177,122,.15);
            filter: blur(45px);
            bottom: -100px;
            right: -30px;
        }

        .brand-content {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 340px;
        }

        .brand-logo {
            font-size: 43px;
            font-weight: 800;
            letter-spacing: -2px;
            margin-bottom: 14px;
            line-height: 1.1;
        }

        .brand-logo span {
            color: var(--peach);
            font-weight: 400;
        }

        .brand-content p {
            color: #E0E2EE;
            font-size: 11px;
            line-height: 1.8;
            max-width: 290px;
        }

        .brand-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 7px;
            margin-top: 18px;
        }

        .brand-tags span {
            font-size: 9px;
            color: #F1F1F7;
            background: rgba(255,255,255,.12);
            border: 1px solid rgba(255,255,255,.08);
            padding: 5px 10px;
            border-radius: 20px;
        }

        .brand-small {
            color: rgba(255,255,255,.55);
            font-size: 9px;
            margin-top: 28px;
        }

        .auth-card.register-mode .form-side {
            transform: translateX(100%);
        }

        .auth-card.register-mode .brand-side {
            transform: translateX(-100%);
        }

        @media (max-width: 700px) {
            body {
                padding: 15px;
            }

            .auth-card {
                height: 620px;
                max-width: 440px;
            }

            .form-side {
                width: 100%;
                height: 72%;
                padding: 35px;
                top: 0;
            }

            .brand-side {
                width: 100%;
                height: 28%;
                top: 72%;
                right: 0;
                padding: 20px 35px;
                justify-content: flex-start;
                align-items: center;
            }

            .brand-logo {
                font-size: 27px;
                margin-bottom: 5px;
            }

            .brand-content p {
                font-size: 9px;
            }

            .brand-tags,
            .brand-small {
                display: none;
            }

            .auth-card.register-mode .form-side {
                transform: translateY(38.9%);
            }

            .auth-card.register-mode .brand-side {
                transform: translateY(-257%);
            }

            .form-panel h1 {
                font-size: 22px;
            }
        }

        @media (max-width: 380px) {
            .form-side {
                padding: 25px;
            }
        }
    </style>
</head>

<body>

<div class="auth-card <?= $activePanel === 'register' ? 'register-mode' : '' ?>" id="authCard">

    <div class="form-side">
        <div class="form-content">

            <!-- SIGN IN -->
            <section class="form-panel <?= $activePanel === 'login' ? 'active' : '' ?>" id="loginPanel">

                <h1>Selamat datang kembali</h1>

                <p class="form-description">
                    Masuk untuk mengelola ONE RENT
                </p>

                <?php if ($activePanel === 'login' && $error): ?>
                    <div class="message error"><?= e($error) ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="message success"><?= e($success) ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <input type="hidden" name="action" value="login">

                    <div class="field">
                        <label for="loginUsername">USERNAME</label>
                        <input
                            type="text"
                            id="loginUsername"
                            name="username"
                            placeholder="Masukkan username"
                            value="<?= e($loginUsername) ?>"
                            maxlength="15"
                            autocomplete="username"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="loginPassword">PASSWORD</label>

                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="loginPassword"
                                name="password"
                                placeholder="Masukkan password"
                                autocomplete="current-password"
                                required
                            >

                            <button
                                type="button"
                                class="toggle-password"
                                onclick="togglePassword('loginPassword', this)"
                                aria-label="Lihat password"
                            >
                                <svg viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.7"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        Masuk
                    </button>
                </form>

                <div class="switch-text">
                    Belum memiliki akun?
                    <button type="button" onclick="showRegister()">Sign Up</button>
                </div>

                <div class="form-footer">
                    Khusus administrator ONE RENT.
                </div>
            </section>

            <!-- SIGN UP -->
            <section class="form-panel <?= $activePanel === 'register' ? 'active' : '' ?>" id="registerPanel">

                <h1>Buat akun admin</h1>

                <p class="form-description">
                    Daftarkan akun baru untuk mengakses ONE RENT
                </p>

                <?php if ($activePanel === 'register' && $error): ?>
                    <div class="message error"><?= e($error) ?></div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <input type="hidden" name="action" value="register">

                    <div class="field">
                        <label for="registerName">NAMA LENGKAP</label>
                        <input
                            type="text"
                            id="registerName"
                            name="nama_admin"
                            placeholder="Masukkan nama lengkap"
                            value="<?= e($registerName) ?>"
                            maxlength="60"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="registerUsername">USERNAME</label>
                        <input
                            type="text"
                            id="registerUsername"
                            name="username"
                            placeholder="Buat username"
                            value="<?= e($registerUsername) ?>"
                            maxlength="15"
                            autocomplete="username"
                            required
                        >
                    </div>

                    <div class="field">
                        <label for="registerPassword">PASSWORD</label>

                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="registerPassword"
                                name="password"
                                placeholder="Minimal 8 karakter"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="toggle-password"
                                onclick="togglePassword('registerPassword', this)"
                                aria-label="Lihat password"
                            >
                                <svg viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.7"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="field">
                        <label for="confirmPassword">KONFIRMASI PASSWORD</label>

                        <div class="password-wrapper">
                            <input
                                type="password"
                                id="confirmPassword"
                                name="confirm_password"
                                placeholder="Ulangi password"
                                minlength="8"
                                autocomplete="new-password"
                                required
                            >

                            <button
                                type="button"
                                class="toggle-password"
                                onclick="togglePassword('confirmPassword', this)"
                                aria-label="Lihat password"
                            >
                                <svg viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="1.7"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="submit-btn">
                        Daftar Akun
                    </button>
                </form>

                <div class="switch-text">
                    Sudah memiliki akun?
                    <button type="button" onclick="showLogin()">Sign In</button>
                </div>
            </section>

        </div>
    </div>

    <!-- BRAND PANEL -->
    <aside class="brand-side">
        <div class="brand-content">

            <div class="brand-logo">
                ONE<span>Rent</span>
            </div>

            <p>
                Kelola armada, reservasi, dan pembayaran
                dalam satu panel.
            </p>

            <div class="brand-tags">
                <span>Armada</span>
                <span>Reservasi</span>
                <span>Pembayaran</span>
            </div>

            <div class="brand-small">
                CAR RENTAL MANAGEMENT
            </div>

        </div>
    </aside>

</div>

<script>
    const authCard = document.getElementById('authCard');
    const loginPanel = document.getElementById('loginPanel');
    const registerPanel = document.getElementById('registerPanel');

    function showRegister() {
        authCard.classList.add('register-mode');
        loginPanel.classList.remove('active');
        registerPanel.classList.add('active');
    }

    function showLogin() {
        authCard.classList.remove('register-mode');
        registerPanel.classList.remove('active');
        loginPanel.classList.add('active');
    }

    function togglePassword(inputId, button) {
        const input = document.getElementById(inputId);

        if (input.type === "password") {
            input.type = "text";

            button.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.7"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 3l18 18"/>
                    <path d="M10.6 10.6a2 2 0 0 0 2.8 2.8"/>
                    <path d="M9.9 5.2A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a14 14 0 0 1-3.1 3.8"/>
                    <path d="M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7a10 10 0 0 0 4-.8"/>
                </svg>
            `;
        } else {
            input.type = "password";

            button.innerHTML = `
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.7"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
            `;
        }
    }
</script>

</body>
</html>
