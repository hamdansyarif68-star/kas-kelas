<?php
session_start();
require_once 'db.php';

// Jika sudah login, tidak perlu registrasi lagi
if (!empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($name == '' || $username == '' || $password == '') {
        $message = 'Semua kolom wajib diisi!';
    } elseif (strlen($password) < 6) {
        $message = 'Password minimal 6 karakter!';
    } elseif ($password !== $confirm) {
        $message = 'Konfirmasi password tidak sama!';
    } elseif (!preg_match('/^[a-zA-Z0-9_.-]{3,50}$/', $username)) {
        $message = 'Format username tidak valid!';
    } else {
        try {
            $check = db()->prepare("SELECT id FROM users WHERE username = ?");
            $check->execute([$username]);

            if ($check->fetch()) {
                $message = 'Username sudah digunakan!';
            } else {
                $stmt = db()->prepare(
                    "INSERT INTO users (name, username, password, role)
                     VALUES (?, ?, ?, 'anggota')"
                );
                $stmt->execute([
                    $name,
                    $username,
                    password_hash($password, PASSWORD_DEFAULT)
                ]);

                header("Location: index.php?registered=1");
                exit;
            }
        } catch (Throwable $e) {
            $message = 'Pendaftaran gagal. Periksa koneksi database!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Registrasi KasKelas</title>
<style>
* { box-sizing: border-box; }
body { margin: 0; font-family: Arial, sans-serif; background: #f3f6fa; color: #172033; }
.container { max-width: 430px; margin: 55px auto; padding: 20px; }
.card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px #00000010; }
h1 { text-align: center; color: #176b53; margin-bottom: 5px; }
h2 { text-align: center; margin-bottom: 10px; }
.subtitle { text-align: center; color: #64748b; font-size: 14px; margin-bottom: 25px; }
label { display: block; margin-bottom: 15px; font-size: 14px; font-weight: bold; }
input { width: 100%; padding: 12px; margin-top: 7px; border: 1px solid #cbd5e1; border-radius: 7px; font-size: 14px; }
button { width: 100%; padding: 13px; border: none; border-radius: 7px; background: #176b53; color: white; font-weight: bold; cursor: pointer; margin-top: 8px; }
button:hover { background: #125640; }
.message { text-align: center; color: #b42318; font-size: 13px; margin: 12px 0; }
.login { text-align: center; font-size: 14px; margin-top: 20px; }
a { color: #176b53; text-decoration: none; font-weight: bold; }
</style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>KasKelas</h1>
        <h2>Daftar Akun</h2>
        <p class="subtitle">Silakan isi data untuk membuat akun baru.</p>

        <?php if ($message !== ''): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <form method="POST">
            <label>Nama Lengkap
                <input type="text" name="name" placeholder="Masukkan nama lengkap" required>
            </label>
            <label>Username
                <input type="text" name="username" placeholder="Masukkan username" required>
            </label>
            <label>Password
                <input type="password" name="password" placeholder="Minimal 6 karakter" required>
            </label>
            <label>Konfirmasi Password
                <input type="password" name="confirm_password" placeholder="Ulangi password" required>
            </label>
            <button type="submit">Daftar Sekarang</button>
        </form>

        <div class="login">Sudah punya akun? <a href="index.php">Login di sini</a></div>
    </div>
</div>
</body>
</html>