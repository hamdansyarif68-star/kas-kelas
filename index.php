<?php
session_start();

// Jika sudah login, tidak perlu tampil halaman login
if (!empty($_SESSION['user'])) {
    header('Location: app.php');
    exit;
}

$registered = isset($_GET['registered']);
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kas Kelas - Aplikasi Kas Kelas</title>
<link rel="stylesheet" href="assets/style.css">
<style>
.register-link{text-align:center;font-size:14px;color:#64748b;margin-top:14px}
.register-link a{color:#176b53;font-weight:bold;text-decoration:none}
.register-link a:hover{text-decoration:underline}
</style>
</head>
<body>
<div class="container">
  <header><div><h1>Kas Kelas</h1><p>Pengelolaan kas kelas secara terpusat</p></div></header>
  <section id="loginView" class="panel login">
    <h2>Masuk ke Aplikasi</h2>

    <?php if ($registered): ?>
      <div class="message success" style="text-align:center;margin-bottom:12px;">Registrasi berhasil! Silakan login.</div>
    <?php endif; ?>

    <form id="loginForm">
      <label>Username<input name="username" required placeholder="Masukkan username"></label>
      <label>Password<input name="password" type="password" required placeholder="Masukkan password"></label>
      <button type="submit">Login</button>
    </form>
    <div id="loginMsg" class="message"></div>
    <p class="register-link">Belum punya akun? <a href="register.php">Daftar di sini</a></p>
  </section>
</div>
<script>
document.getElementById('loginForm').addEventListener('submit', async function(e){
  e.preventDefault();
  const f = new FormData(e.target);
  const msg = document.getElementById('loginMsg');
  msg.textContent = '';
  msg.className = 'message';
  try {
    const res = await fetch('api.php?action=login', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify(Object.fromEntries(f))
    });
    const json = await res.json();
    if (!res.ok || !json.ok) throw new Error(json.message || 'Login gagal');
    // Login berhasil -> pindah ke halaman aplikasi
    window.location.href = 'app.php';
  } catch (err) {
    msg.textContent = err.message;
    msg.className = 'message error';
  }
});
</script>
</body></html>