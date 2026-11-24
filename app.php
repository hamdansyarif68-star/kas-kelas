<?php
session_start();
if (empty($_SESSION['user'])) {
    header('Location: index.php');
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kas Kelas - Aplikasi Kas Kelas</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="container">
  <header>
    <div><h1>Kas Kelas</h1><p>Pengelolaan kas kelas secara terpusat</p></div>
    <button id="logoutBtn" class="secondary">Keluar</button>
  </header>

  <main id="appView">
    <section class="welcome">
      <div><h2 id="welcomeName">Dashboard</h2><p id="roleText"></p></div>
      <span class="server-tag">Terhubung ke server pusat</span>
    </section>

    <section class="stats">
      <article class="stat"><span>Total Pemasukan</span><strong id="income">Rp0</strong></article>
      <article class="stat"><span>Total Pengeluaran</span><strong id="expense">Rp0</strong></article>
      <article class="stat"><span>Saldo Kas</span><strong id="balance">Rp0</strong></article>
    </section>

    <section id="transactionFormPanel" class="panel hidden">
      <h3>Tambah Transaksi</h3>
      <form id="transactionForm" class="form-grid">
        <label>Jenis<select name="type"><option value="pemasukan">Pemasukan</option><option value="pengeluaran">Pengeluaran</option></select></label>
        <label>NIM (Opsional)<input name="nim" placeholder="Masukkan NIM"></label>
        <label>Jumlah (Rp)<input name="amount" type="number" min="1" required></label>
        <label>Tanggal<input name="transaction_date" type="date" required></label>
        <label class="wide">Keterangan<input name="description" required placeholder="Keterangan transaksi"></label>
        <div class="wide"><button type="submit">Simpan Transaksi</button><span id="transactionMsg" class="message"></span></div>
      </form>
    </section>

    <section id="userFormPanel" class="panel hidden">
      <h3>Tambah Pengguna (Admin)</h3>
      <form id="userForm" class="form-grid">
        <label>Nama<input name="name" required></label><label>Username<input name="username" required></label>
        <label>Password<input name="password" type="password" minlength="6" required></label>
        <label>Peran<select name="role"><option value="anggota">Anggota</option><option value="bendahara">Bendahara</option><option value="admin">Admin</option></select></label>
        <div class="wide"><button type="submit">Tambah Pengguna</button><span id="userMsg" class="message"></span></div>
      </form>
      <div id="usersList"></div>
    </section>

    <section class="panel">
      <div class="section-head">
        <div><h3>Riwayat Transaksi</h3><p class="hint">Data terbaru dari Google Sheets</p></div>
        <button id="refreshBtn" class="secondary">Muat ulang</button>
      </div>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Tanggal</th>
              <th>NIM</th>
              <th>Nama</th>
              <th>Jenis</th>
              <th>Keterangan</th>
              <th>Jumlah</th>
              <th id="actionHead" class="hidden">Aksi</th>
            </tr>
          </thead>
          <tbody id="transactionRows"></tbody>
        </table>
      </div>
    </section>

    <p class="footnote">KasKelas · Client terhubung melalui REST API · <span id="lastUpdate"></span></p>
  </main>
</div>
<script src="assets/app.js?v=2"></script>
</body></html>