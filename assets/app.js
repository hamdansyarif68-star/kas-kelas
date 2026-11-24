const $ = s => document.querySelector(s);
let currentUser = null;

const rupiah = n => new Intl.NumberFormat('id-ID', {style:'currency', currency:'IDR', maximumFractionDigits:0}).format(Number(n || 0));

async function api(action, data = null) {
  const options = { method: data ? 'POST' : 'GET', headers: {'Content-Type':'application/json'} };
  if (data) options.body = JSON.stringify(data);
  const res = await fetch('api.php?action=' + encodeURIComponent(action), options);
  const json = await res.json();
  if (!res.ok || !json.ok) throw new Error(json.message || 'Permintaan gagal');
  return json;
}

function showMessage(selector, msg, isError = false) {
  const el = $(selector);
  el.textContent = msg;
  el.className = 'message ' + (isError ? 'error' : 'success');
}

function roleName(role) {
  return ({admin:'Administrator', bendahara:'Bendahara', anggota:'Anggota'})[role] || role;
}

function showApp(user) {
  currentUser = user;
  $('#welcomeName').textContent = 'Halo, ' + user.name;
  $('#roleText').textContent = 'Masuk sebagai ' + roleName(user.role);

  if (['admin','bendahara'].includes(user.role)) {
    $('#transactionFormPanel').classList.remove('hidden');
  }
  if (user.role === 'admin') {
    $('#userFormPanel').classList.remove('hidden');
    $('#actionHead').classList.remove('hidden');
  }

  loadDashboard();
  if (user.role === 'admin') loadUsers();
}

async function loadDashboard() {
  try {
    const d = await api('dashboard');
    $('#income').textContent = rupiah(d.summary.pemasukan);
    $('#expense').textContent = rupiah(d.summary.pengeluaran);
    $('#balance').textContent = rupiah(d.summary.saldo);

    const rows = $('#transactionRows');
    rows.innerHTML = '';
    
    if (!d.transactions || d.transactions.length === 0) {
      rows.innerHTML = '<tr><td colspan="7" class="empty">Belum ada transaksi kas.</td></tr>';
    } else {
      d.transactions.forEach(t => {
        const tr = document.createElement('tr');
        
        // URUTAN DISESUAIKAN DENGAN HEADER TABEL DI app.php
        const values = [
            t.transaction_date,  // 1. Tanggal
            t.nim || '-',        // 2. NIM
            t.creator || '-',    // 3. Nama
            t.type === 'pemasukan' ? 'Pemasukan' : 'Pengeluaran', // 4. Jenis
            t.description || '-',// 5. Keterangan
            t.amount             // 6. Jumlah
        ];
        
        values.forEach((v, i) => {
          const td = document.createElement('td');
          // Format Rupiah hanya untuk index ke-5 (Kolom Jumlah)
          td.textContent = (i === 5) ? rupiah(v) : v;
          tr.appendChild(td);
        });
        
        if (currentUser.role === 'admin') {
          const td = document.createElement('td');
          const b = document.createElement('button');
          b.textContent = 'Hapus';
          b.className = 'danger small';
          b.onclick = () => deleteTransaction(t.id);
          td.appendChild(b);
          tr.appendChild(td);
        }
        rows.appendChild(tr);
      });
    }
    $('#lastUpdate').textContent = 'Terakhir diperbarui: ' + new Date().toLocaleTimeString('id-ID');
  } catch (e) {
    console.error(e);
  }
}

async function deleteTransaction(id) {
  if (!confirm('Hapus transaksi ini?')) return;
  try {
    await api('delete_transaction', {id});
    loadDashboard();
  } catch (e) { alert(e.message); }
}

async function loadUsers() {
  try {
    const d = await api('users');
    const box = $('#usersList');
    box.innerHTML = '<h4>Daftar Pengguna</h4>';
    d.users.forEach(u => {
      const row = document.createElement('div');
      row.className = 'user-row';
      const span = document.createElement('span');
      span.textContent = u.name + ' (' + u.username + ') — ' + roleName(u.role);
      row.appendChild(span);

      const b = document.createElement('button');
      b.className = 'danger small';
      b.textContent = 'Hapus';
      b.onclick = async () => {
        if (confirm('Hapus pengguna ' + u.name + '?')) {
          try { await api('delete_user', {id: u.id}); loadUsers(); }
          catch (e) { alert(e.message); }
        }
      };
      row.appendChild(b);
      box.appendChild(row);
    });
  } catch (e) { console.error(e); }
}

$('#logoutBtn').onclick = async () => {
  try { await api('logout'); } catch (e) {}
  window.location.href = 'index.php';
};

$('#transactionForm').addEventListener('submit', async e => {
  e.preventDefault();
  const d = Object.fromEntries(new FormData(e.target));
  try {
    const r = await api('add_transaction', d);
    showMessage('#transactionMsg', r.message);
    e.target.reset();
    e.target.elements.transaction_date.value = new Date().toISOString().slice(0, 10);
    loadDashboard();
  } catch (err) {
    showMessage('#transactionMsg', err.message, true);
  }
});

$('#userForm').addEventListener('submit', async e => {
  e.preventDefault();
  try {
    const r = await api('add_user', Object.fromEntries(new FormData(e.target)));
    showMessage('#userMsg', r.message);
    e.target.reset();
    loadUsers();
  } catch (err) {
    showMessage('#userMsg', err.message, true);
  }
});

$('#refreshBtn').onclick = loadDashboard;

(async () => {
  try {
    const d = await api('me');
    if (!d.user) { window.location.href = 'index.php'; return; }
    showApp(d.user);
  } catch (e) {
    window.location.href = 'index.php';
  }
})();

setInterval(() => { if (currentUser) loadDashboard(); }, 15000);