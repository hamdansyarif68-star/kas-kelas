<?php
require_once __DIR__ . '/db.php';
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS'])]);
session_start();
header('Content-Type: application/json; charset=utf-8');

function out($data, int $status=200): void {
    http_response_code($status); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit;
}
function body(): array {
    $raw=file_get_contents('php://input'); $d=json_decode($raw,true);
    return is_array($d) ? $d : $_POST;
}
function need_login(): array {
    if (empty($_SESSION['user'])) out(['ok'=>false,'message'=>'Silakan login terlebih dahulu.'],401);
    return $_SESSION['user'];
}
function need_role(array $allowed): array {
    $u=need_login();
    if (!in_array($u['role'],$allowed,true)) out(['ok'=>false,'message'=>'Anda tidak memiliki hak akses.'],403);
    return $u;
}

// Fungsi request ke Google Apps Script (Diperbaiki menggunakan GET & cURL)
function callGoogle($method = 'GET', $params = []) {
    $url = APPS_SCRIPT_URL;
    if ($method === 'GET') {
        $url .= '?' . http_build_query($params);
    }
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Wajib true untuk redirect Google
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return json_decode($response, true);
}

try {
    $action=$_GET['action'] ?? '';
    $data=body();
    
    if ($action==='login') {
        $stmt=db()->prepare('SELECT id,name,username,password,role FROM users WHERE username=?');
        $stmt->execute([trim($data['username'] ?? '')]); $u=$stmt->fetch();
        if (!$u || !password_verify($data['password'] ?? '',$u['password'])) out(['ok'=>false,'message'=>'Username atau password salah.'],401);
        session_regenerate_id(true);
        unset($u['password']); $_SESSION['user']=$u;
        out(['ok'=>true,'user'=>$u]);
    }
    if ($action==='logout') { $_SESSION=[]; session_destroy(); out(['ok'=>true]); }
    if ($action==='me') out(['ok'=>true,'user'=>$_SESSION['user'] ?? null]);

    $user=need_login();
    
    if ($action==='dashboard' || $action==='transactions') {
        // Ambil data dari Google Sheets
        $result = callGoogle('GET', ['action' => 'getKas']);
        $rawTransactions = $result['data'] ?? [];
        
        $income = 0;
        $expense = 0;
        $formatted = [];
        
        foreach($rawTransactions as $index => $t) {
            $amount = (float)($t['Jumlah'] ?? 0);
            $isPemasukan = (strtolower($t['Jenis'] ?? '') === 'pemasukan');
            
            if ($isPemasukan) $income += $amount;
            else $expense += $amount;
            
            $formatted[] = [
                'id' => $t['_rowNumber'] ?? ($index + 1),
                'type' => $isPemasukan ? 'pemasukan' : 'pengeluaran',
                'description' => $t['Keterangan'] ?? '-',
                'amount' => $amount,
                'transaction_date' => $t['Tanggal'] ?? '-',
                'creator' => $t['Nama'] ?? '-',
                'nim' => $t['NIM'] ?? '-'
            ];
        }
        
        if ($action === 'transactions') {
            out(['ok'=>true,'transactions'=>array_reverse($formatted)]);
        } else {
            out(['ok'=>true,'summary'=>['pemasukan'=>$income,'pengeluaran'=>$expense,'saldo'=>$income-$expense],
                 'transactions'=>array_reverse($formatted)]);
        }
    }

    if ($action==='add_transaction' && $_SERVER['REQUEST_METHOD']==='POST') {
        need_role(['admin','bendahara']);
        $type=$data['type'] ?? ''; 
        $desc=trim($data['description'] ?? ''); 
        $amount=(float)($data['amount'] ?? 0);
        $date=$data['transaction_date'] ?? date('Y-m-d');
        $nim=trim($data['nim'] ?? '-');
        
        if (!in_array($type,['pemasukan','pengeluaran'],true) || $desc==='' || $amount<=0) out(['ok'=>false,'message'=>'Lengkapi data dengan benar.'],422);
        
        callGoogle('POST', [
            'action' => 'tambahKas',
            'data' => [
                'tanggal' => $date,
                'nim' => $nim,
                'nama' => $user['name'],
                'jenis' => ($type == 'pemasukan') ? 'Pemasukan' : 'Pengeluaran',
                'keterangan' => $desc,
                'jumlah' => $amount
            ]
        ]);
        
        out(['ok'=>true,'message'=>'Transaksi berhasil disimpan ke Google Sheets.']);
    }

    if ($action==='delete_transaction' && $_SERVER['REQUEST_METHOD']==='POST') {
        need_role(['admin']);
        $rowId = (int)($data['id'] ?? 0);
        callGoogle('POST', ['action' => 'hapusKas', 'id' => $rowId]);
        out(['ok'=>true,'message'=>'Transaksi berhasil dihapus.']);
    }

    if ($action==='users') {
        need_role(['admin']);
        out(['ok'=>true,'users'=>db()->query('SELECT id,name,username,role,created_at FROM users ORDER BY name')->fetchAll()]);
    }
    
    if ($action==='add_user' && $_SERVER['REQUEST_METHOD']==='POST') {
        need_role(['admin']);
        $name=trim($data['name'] ?? ''); $username=trim($data['username'] ?? '');
        $password=$data['password'] ?? ''; $role=$data['role'] ?? 'anggota';
        if ($name==='' || $username==='' || strlen($password)<6 || !in_array($role,['admin','bendahara','anggota'],true)) out(['ok'=>false,'message'=>'Data tidak valid.'],422);
        try {
            $stmt=db()->prepare('INSERT INTO users(name,username,password,role) VALUES(?,?,?,?)');
            $stmt->execute([$name,$username,password_hash($password,PASSWORD_DEFAULT),$role]);
        } catch (PDOException $e) { out(['ok'=>false,'message'=>'Username sudah digunakan.'],409); }
        out(['ok'=>true,'message'=>'Pengguna berhasil ditambahkan.']);
    }
    
    if ($action==='delete_user' && $_SERVER['REQUEST_METHOD']==='POST') {
        need_role(['admin']);
        $id=(int)($data['id'] ?? 0);
        if ($id===$user['id']) out(['ok'=>false,'message'=>'Tidak dapat menghapus diri sendiri.'],422);
        $stmt=db()->prepare('DELETE FROM users WHERE id=?'); $stmt->execute([$id]);
        out(['ok'=>true,'message'=>'Pengguna dihapus.']);
    }
    
    out(['ok'=>false,'message'=>'Endpoint tidak ditemukan.'],404);
} catch (Throwable $e) {
    out(['ok'=>false,'message'=>'Kesalahan server: ' . $e->getMessage()],500);
}