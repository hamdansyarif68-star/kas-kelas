<?php
require_once __DIR__ . '/config.php';
try {
    $pdo = new PDO('mysql:host=' . DB_HOST . ';charset=utf8mb4', DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `".DB_NAME."`");
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin','bendahara','anggota') NOT NULL DEFAULT 'anggota',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        type ENUM('pemasukan','pengeluaran') NOT NULL,
        category VARCHAR(80) NOT NULL,
        description VARCHAR(255) NOT NULL,
        amount DECIMAL(12,2) NOT NULL,
        transaction_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_transaction_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare("INSERT INTO users(name,username,password,role) VALUES(?,?,?,?)");
        $seeds = [
            ['Administrator','admin','admin123','admin'],
            ['Bendahara Kelas','bendahara','bendahara123','bendahara'],
            ['Anggota Kelas','anggota','anggota123','anggota']
        ];
        foreach ($seeds as $u) $stmt->execute([$u[0],$u[1],password_hash($u[2], PASSWORD_DEFAULT),$u[3]]);
    }
    echo '<h2>Instalasi berhasil!</h2><p>Database dan tabel telah dibuat.</p>
    <p>Login awal: admin / admin123, bendahara / bendahara123, anggota / anggota123.</p>
    <p><b>Hapus atau ubah nama setup.php setelah instalasi.</b></p><a href="index.php">Buka aplikasi</a>';
} catch (Throwable $e) {
    http_response_code(500);
    echo 'Instalasi gagal: ' . htmlspecialchars($e->getMessage());
}
