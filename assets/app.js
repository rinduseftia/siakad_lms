/* ═══════════════════════════════════════════════════════════════
   SIAKAD v2 — app.js
   Multi-role auth, nama tampil, bulk delete, NIM-based API
═══════════════════════════════════════════════════════════════ */

const API = 'api';   // path relatif dari index.html

/* ─────────────────────────────────────────────
   STATE GLOBAL
───────────────────────────────────────────── */
let currentUser = null;   // { id, username, role, nama }

/* ═══════════════════════════════════════════
   LOGIN & LOGOUT
═══════════════════════════════════════════ */
async function doLogin() {
  const username = document.getElementById('username').value.trim();
  const password = document.getElementById('password').value;
  const errBox   = document.getElementById('login-error');

  errBox.style.display = 'none';

  if (!username || !password) {
    showLoginError('Username dan password wajib diisi.');
    return;
  }

  // Loading state
  document.getElementById('btn-login-text').style.display = 'none';
  document.getElementById('btn-login-spin').style.display = 'inline';
  document.getElementById('btn-login').disabled = true;

  try {
    const res  = await fetch(`${API}/login.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ username, password })
    });
    const data = await res.json();

    if (data.success) {
      currentUser = data.user;
      applyUserContext();          // tampilkan nama + atur role
      document.getElementById('login-page').style.display = 'none';
      document.getElementById('main-app').style.display   = 'flex';
      loadDashboard();
    } else {
      showLoginError(data.error || 'Login gagal.');
    }
  } catch (e) {
    showLoginError('Tidak dapat terhubung ke server. Pastikan server berjalan.');
  } finally {
    document.getElementById('btn-login-text').style.display = 'inline';
    document.getElementById('btn-login-spin').style.display = 'none';
    document.getElementById('btn-login').disabled = false;
  }
}

function showLoginError(msg) {
  const el = document.getElementById('login-error');
  el.textContent = msg;
  el.style.display = 'block';
}

/* Terapkan info user ke seluruh UI setelah login */
function applyUserContext() {
  if (!currentUser) return;

  const { nama, role, username } = currentUser;
  const initials = getInitials(nama);
  const roleLabel = { admin: 'Administrator', mahasiswa: 'Mahasiswa', dosen: 'Dosen' }[role] || role;

  // Topbar
  document.getElementById('admin-name').textContent   = nama;
  document.getElementById('topbar-role').textContent  = roleLabel;
  document.getElementById('topbar-avatar').textContent = initials;

  // Sidebar
  document.getElementById('sidebar-nama').textContent = nama;
  document.getElementById('sidebar-role').textContent = roleLabel;
  document.getElementById('sidebar-avatar').textContent = initials;

  // Dashboard welcome
  document.getElementById('welcome-name').textContent = nama;
  document.getElementById('welcome-role-info').innerHTML =
    `Anda masuk sebagai <strong>${roleLabel}</strong>` +
    (role === 'admin' ? ' — kelola seluruh data akademik dari sini.' : '.');

  // Tambahkan class role ke body agar CSS sembunyikan .admin-only
  document.body.className = `role-${role}`;
}

function getInitials(nama) {
  if (!nama) return '??';
  const parts = nama.split(' ');
  return (parts[0][0] + (parts[1] ? parts[1][0] : '')).toUpperCase();
}

async function doLogout() {
  if (!confirm('Yakin ingin logout?')) return;
  try {
    await fetch(`${API}/logout.php`, { method: 'POST', credentials: 'include' });
  } catch (_) {}
  currentUser = null;
  document.body.className = '';
  document.getElementById('main-app').style.display   = 'none';
  document.getElementById('login-page').style.display = 'flex';
  document.getElementById('username').value = '';
  document.getElementById('password').value = '';
}

/* ═══════════════════════════════════════════
   NAVIGASI
═══════════════════════════════════════════ */
const PAGE_TITLES = {
  dashboard: 'Dashboard',
  mahasiswa: 'Data Mahasiswa',
  dosen:     'Data Dosen',
  matkul:    'Mata Kuliah',
};

function showPage(name, el) {
  // Sembunyikan semua halaman
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));

  document.getElementById(`page-${name}`).classList.add('active');
  if (el) el.classList.add('active');

  const title = PAGE_TITLES[name] || name;
  document.getElementById('page-heading').textContent    = title;
  document.getElementById('breadcrumb-page').textContent = title;

  // Muat data sesuai halaman
  if (name === 'dashboard') loadDashboard();
  if (name === 'mahasiswa') loadMahasiswa();
  if (name === 'dosen')     loadDosen();
  if (name === 'matkul')    loadMatKul();
}

/* ═══════════════════════════════════════════
   DASHBOARD
═══════════════════════════════════════════ */
async function loadDashboard() {
  try {
    const res  = await fetch(`${API}/dashboard.php`, { credentials: 'include' });
    const data = await res.json();

    if (data.error) { showToast(data.error, 'error'); return; }

    // Statistik
    document.getElementById('stat-mhs').textContent = data.statistik.total_mahasiswa ?? 0;
    document.getElementById('stat-dsn').textContent = data.statistik.total_dosen ?? 0;
    document.getElementById('stat-mk').textContent  = data.statistik.total_matkul ?? 0;

    // Nama dari server (fallback ke currentUser.nama)
    if (data.user_aktif?.nama) {
      document.getElementById('welcome-name').textContent = data.user_aktif.nama;
    }

    // Tabel mahasiswa terbaru
    const mhsTbody = document.getElementById('tbl-mhs-terbaru');
    mhsTbody.innerHTML = data.mahasiswa_terbaru?.length
      ? data.mahasiswa_terbaru.map(m => `
          <tr>
            <td>${m.nim}</td>
            <td>${m.nama}</td>
            <td>${m.program_studi || '–'}</td>
          </tr>`).join('')
      : '<tr><td colspan="3" class="loading-cell">Belum ada data</td></tr>';

    // Tabel dosen terbaru
    const dsnTbody = document.getElementById('tbl-dsn-terbaru');
    dsnTbody.innerHTML = data.dosen_terbaru?.length
      ? data.dosen_terbaru.map(d => `
          <tr>
            <td>${d.nidn}</td>
            <td>${d.nama}</td>
            <td><span class="badge ${d.status === 'Aktif' ? 'badge-aktif' : 'badge-nonaktif'}">${d.status}</span></td>
          </tr>`).join('')
      : '<tr><td colspan="3" class="loading-cell">Belum ada data</td></tr>';

    // Tabel matkul terbaru
    const mkTbody = document.getElementById('tbl-mk-terbaru');
    mkTbody.innerHTML = data.matkul_terbaru?.length
      ? data.matkul_terbaru.map(mk => `
          <tr>
            <td><code style="font-size:.72rem">${mk.kode}</code></td>
            <td>${mk.nama}</td>
            <td>${mk.sks} SKS</td>
          </tr>`).join('')
      : '<tr><td colspan="3" class="loading-cell">Belum ada data</td></tr>';

  } catch (e) {
    console.error('Dashboard error:', e);
  }
}

/* ═══════════════════════════════════════════
   MAHASISWA
═══════════════════════════════════════════ */
async function loadMahasiswa() {
  try {
    const res  = await fetch(`${API}/mahasiswa.php`, { credentials: 'include' });
    const data = await res.json();
    const tbody = document.getElementById('tbl-mhs-body');

    if (!Array.isArray(data) || data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="14" class="loading-cell">Belum ada data mahasiswa</td></tr>';
      return;
    }

    tbody.innerHTML = data.map((m, i) => {
      const isAdmin = currentUser?.role === 'admin';
      return `<tr>
        ${isAdmin ? `<td class="cb-col"><input type="checkbox" class="cb-mhs" value="${m.nim}" onchange="updateBulkBtn('mhs')"></td>` : ''}
        <td>${i + 1}</td>
        <td><strong>${m.nim}</strong></td>
        <td>${m.nama}</td>
        <td>${m.jur || '–'}</td>
        <td>${m.program_studi || '–'}</td>
        <td>${m.email || '–'}</td>
        <td>${m.agama || '–'}</td>
        <td><span class="badge ${m.status === 'Aktif' ? 'badge-aktif' : 'badge-nonaktif'}">${m.status || '–'}</span></td>
        <td><span class="badge ${m.jk === 'Laki-laki' ? 'badge-lk' : 'badge-pr'}">${m.jk || '–'}</span></td>
        <td>${m.tmp_lahir || '–'}</td>
        <td>${m.tgl_lahir && m.tgl_lahir !== '0000-00-00' ? m.tgl_lahir : '–'}</td>
        <td title="${m.alamat}">${m.alamat ? (m.alamat.length > 30 ? m.alamat.slice(0,30)+'…' : m.alamat) : '–'}</td>
        ${isAdmin ? `<td><div class="action-btns">
          <button class="btn-edit" onclick="editMahasiswa('${m.nim}')">Edit</button>
          <button class="btn-del"  onclick="deleteMahasiswa('${m.nim}', '${m.nama}')">Hapus</button>
        </div></td>` : ''}
      </tr>`;
    }).join('');

    // Setup "pilih semua"
    const cbAll = document.getElementById('cb-all-mhs');
    if (cbAll) cbAll.onchange = () => toggleAllCb('cb-mhs', cbAll.checked, 'mhs');

  } catch (e) {
    document.getElementById('tbl-mhs-body').innerHTML =
      '<tr><td colspan="14" class="loading-cell">Gagal memuat data</td></tr>';
  }
}

function clearFormMhs() {
  document.getElementById('modal-mhs-title').textContent = 'Tambah Mahasiswa';
  document.getElementById('mhs-nim-old').value   = '';
  document.getElementById('mhs-nim').value        = '';
  document.getElementById('mhs-nim').readOnly     = false;
  document.getElementById('mhs-nama').value       = '';
  document.getElementById('mhs-jur').value        = '';
  document.getElementById('mhs-program_studi').value = '';
  document.getElementById('mhs-email').value      = '';
  document.getElementById('mhs-agama').value      = 'Islam';
  document.getElementById('mhs-status').value     = 'Aktif';
  document.getElementById('mhs-jk').value         = 'Laki-laki';
  document.getElementById('mhs-tmp_lahir').value  = '';
  document.getElementById('mhs-tgl_lahir').value  = '';
  document.getElementById('mhs-alamat').value     = '';
}

async function editMahasiswa(nim) {
  try {
    const res  = await fetch(`${API}/mahasiswa.php?nim=${nim}`, { credentials: 'include' });
    const data = await res.json();
    if (data.error) { showToast(data.error, 'error'); return; }

    document.getElementById('modal-mhs-title').textContent = 'Edit Mahasiswa';
    document.getElementById('mhs-nim-old').value   = data.nim;
    document.getElementById('mhs-nim').value        = data.nim;
    document.getElementById('mhs-nim').readOnly     = true;   // NIM tidak boleh diubah saat edit
    document.getElementById('mhs-nama').value       = data.nama || '';
    document.getElementById('mhs-jur').value        = data.jur || '';
    document.getElementById('mhs-program_studi').value = data.program_studi || '';
    document.getElementById('mhs-email').value      = data.email || '';
    document.getElementById('mhs-agama').value      = data.agama || 'Islam';
    document.getElementById('mhs-status').value     = data.status || 'Aktif';
    document.getElementById('mhs-jk').value         = data.jk || 'Laki-laki';
    document.getElementById('mhs-tmp_lahir').value  = data.tmp_lahir || '';
    document.getElementById('mhs-tgl_lahir').value  = data.tgl_lahir || '';
    document.getElementById('mhs-alamat').value     = data.alamat || '';

    openModal('modal-mhs');
  } catch (e) {
    showToast('Gagal memuat data mahasiswa', 'error');
  }
}

async function saveMahasiswa() {
  const nimOld = document.getElementById('mhs-nim-old').value;
  const nim    = document.getElementById('mhs-nim').value.trim();
  const nama   = document.getElementById('mhs-nama').value.trim();

  if (!nim || !nama) { showToast('NIM dan Nama wajib diisi', 'error'); return; }

  const payload = {
    nim,
    nama,
    jur:           document.getElementById('mhs-jur').value,
    program_studi: document.getElementById('mhs-program_studi').value,
    email:         document.getElementById('mhs-email').value.trim(),
    agama:         document.getElementById('mhs-agama').value,
    status:        document.getElementById('mhs-status').value,
    jk:            document.getElementById('mhs-jk').value,
    tmp_lahir:     document.getElementById('mhs-tmp_lahir').value.trim(),
    tgl_lahir:     document.getElementById('mhs-tgl_lahir').value,
    alamat:        document.getElementById('mhs-alamat').value.trim(),
  };

  const isEdit = !!nimOld;
  const url    = isEdit ? `${API}/mahasiswa.php?nim=${nimOld}` : `${API}/mahasiswa.php`;
  const method = isEdit ? 'PUT' : 'POST';

  try {
    const res  = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(payload)
    });
    const data = await res.json();

    if (data.success) {
      showToast(isEdit ? 'Data mahasiswa berhasil diperbarui' : 'Mahasiswa berhasil ditambahkan');
      closeModal('modal-mhs');
      loadMahasiswa();
    } else {
      showToast(data.error || 'Gagal menyimpan data', 'error');
    }
  } catch (e) {
    showToast('Terjadi kesalahan. Coba lagi.', 'error');
  }
}

async function deleteMahasiswa(nim, nama) {
  if (!confirm(`Hapus mahasiswa "${nama}" (NIM: ${nim})?\n\nAkun login mahasiswa ini juga akan dihapus.`)) return;
  try {
    const res  = await fetch(`${API}/mahasiswa.php?nim=${nim}`, {
      method: 'DELETE',
      credentials: 'include'
    });
    const data = await res.json();
    if (data.success) {
      showToast('Mahasiswa berhasil dihapus');
      loadMahasiswa();
    } else {
      showToast(data.error || 'Gagal menghapus', 'error');
    }
  } catch (e) {
    showToast('Terjadi kesalahan', 'error');
  }
}

async function bulkDeleteMahasiswa() {
  const checked = [...document.querySelectorAll('.cb-mhs:checked')];
  if (checked.length === 0) return;

  const nims = checked.map(c => c.value);
  if (!confirm(`Hapus ${nims.length} mahasiswa yang dipilih?\n\nAkun login mereka juga akan dihapus.`)) return;

  try {
    const res  = await fetch(`${API}/mahasiswa.php`, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ nims })
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message || `${nims.length} mahasiswa dihapus`);
      document.getElementById('btn-bulk-mhs').style.display = 'none';
      document.getElementById('count-mhs').textContent = '0';
      loadMahasiswa();
    } else {
      showToast(data.error || 'Gagal menghapus', 'error');
    }
  } catch (e) {
    showToast('Terjadi kesalahan', 'error');
  }
}

/* ═══════════════════════════════════════════
   DOSEN
═══════════════════════════════════════════ */
async function loadDosen() {
  try {
    const res  = await fetch(`${API}/dosen.php`, { credentials: 'include' });
    const data = await res.json();
    const tbody = document.getElementById('tbl-dsn-body');

    if (!Array.isArray(data) || data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="8" class="loading-cell">Belum ada data dosen</td></tr>';
      return;
    }

    const isAdmin = currentUser?.role === 'admin';
    tbody.innerHTML = data.map((d, i) => `<tr>
      ${isAdmin ? `<td class="cb-col"><input type="checkbox" class="cb-dsn" value="${d.id}" onchange="updateBulkBtn('dsn')"></td>` : ''}
      <td>${i + 1}</td>
      <td><strong>${d.nidn}</strong></td>
      <td>${d.nama}</td>
      <td>${d.email || '–'}</td>
      <td>${d.no_hp || '–'}</td>
      <td><span class="badge ${d.status === 'Aktif' ? 'badge-aktif' : 'badge-nonaktif'}">${d.status}</span></td>
      ${isAdmin ? `<td><div class="action-btns">
        <button class="btn-edit" onclick="editDosen(${d.id})">Edit</button>
        <button class="btn-del"  onclick="deleteDosen(${d.id}, '${d.nama}')">Hapus</button>
      </div></td>` : ''}
    </tr>`).join('');

    const cbAll = document.getElementById('cb-all-dsn');
    if (cbAll) cbAll.onchange = () => toggleAllCb('cb-dsn', cbAll.checked, 'dsn');

  } catch (e) {
    document.getElementById('tbl-dsn-body').innerHTML =
      '<tr><td colspan="8" class="loading-cell">Gagal memuat data</td></tr>';
  }
}

function clearFormDsn() {
  document.getElementById('modal-dsn-title').textContent = 'Tambah Dosen';
  ['dsn-id','dsn-nidn','dsn-nama','dsn-email','dsn-hp'].forEach(id => {
    document.getElementById(id).value = '';
  });
  document.getElementById('dsn-status').value = 'Aktif';
}

async function editDosen(id) {
  try {
    const res  = await fetch(`${API}/dosen.php?id=${id}`, { credentials: 'include' });
    const data = await res.json();
    if (data.error) { showToast(data.error, 'error'); return; }

    document.getElementById('modal-dsn-title').textContent = 'Edit Dosen';
    document.getElementById('dsn-id').value    = data.id;
    document.getElementById('dsn-nidn').value  = data.nidn || '';
    document.getElementById('dsn-nama').value  = data.nama || '';
    document.getElementById('dsn-email').value = data.email || '';
    document.getElementById('dsn-hp').value    = data.no_hp || '';
    document.getElementById('dsn-status').value = data.status || 'Aktif';

    openModal('modal-dsn');
  } catch (e) {
    showToast('Gagal memuat data dosen', 'error');
  }
}

async function saveDosen() {
  const id   = document.getElementById('dsn-id').value;
  const nidn = document.getElementById('dsn-nidn').value.trim();
  const nama = document.getElementById('dsn-nama').value.trim();

  if (!nidn || !nama) { showToast('NIDN dan Nama wajib diisi', 'error'); return; }

  const payload = {
    nidn, nama,
    email:  document.getElementById('dsn-email').value.trim(),
    no_hp:  document.getElementById('dsn-hp').value.trim(),
    status: document.getElementById('dsn-status').value,
  };

  const isEdit = !!id;
  const url    = isEdit ? `${API}/dosen.php?id=${id}` : `${API}/dosen.php`;
  const method = isEdit ? 'PUT' : 'POST';

  try {
    const res  = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      showToast(isEdit ? 'Data dosen berhasil diperbarui' : 'Dosen berhasil ditambahkan');
      closeModal('modal-dsn');
      loadDosen();
    } else {
      showToast(data.error || 'Gagal menyimpan', 'error');
    }
  } catch (e) {
    showToast('Terjadi kesalahan', 'error');
  }
}

async function deleteDosen(id, nama) {
  if (!confirm(`Hapus dosen "${nama}"?\nAkun login dosen ini juga akan dihapus.`)) return;
  try {
    const res  = await fetch(`${API}/dosen.php?id=${id}`, {
      method: 'DELETE', credentials: 'include'
    });
    const data = await res.json();
    if (data.success) { showToast('Dosen berhasil dihapus'); loadDosen(); }
    else showToast(data.error || 'Gagal menghapus', 'error');
  } catch (e) {
    showToast('Terjadi kesalahan', 'error');
  }
}

async function bulkDeleteDosen() {
  const checked = [...document.querySelectorAll('.cb-dsn:checked')];
  if (checked.length === 0) return;

  const ids = checked.map(c => parseInt(c.value));
  if (!confirm(`Hapus ${ids.length} dosen yang dipilih?\nAkun login mereka juga akan dihapus.`)) return;

  try {
    const res  = await fetch(`${API}/dosen.php`, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ ids })
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message || `${ids.length} dosen dihapus`);
      document.getElementById('btn-bulk-dsn').style.display = 'none';
      document.getElementById('count-dsn').textContent = '0';
      loadDosen();
    } else showToast(data.error || 'Gagal menghapus', 'error');
  } catch (e) {
    showToast('Terjadi kesalahan', 'error');
  }
}

/* ═══════════════════════════════════════════
   MATA KULIAH
═══════════════════════════════════════════ */
async function loadMatKul() {
  try {
    const res  = await fetch(`${API}/mata_kuliah.php`, { credentials: 'include' });
    const data = await res.json();
    const tbody = document.getElementById('tbl-mk-body');

    if (!Array.isArray(data) || data.length === 0) {
      tbody.innerHTML = '<tr><td colspan="7" class="loading-cell">Belum ada data mata kuliah</td></tr>';
      return;
    }

    const isAdmin = currentUser?.role === 'admin';
    tbody.innerHTML = data.map((mk, i) => `<tr>
      ${isAdmin ? `<td class="cb-col"><input type="checkbox" class="cb-mk" value="${mk.id}" onchange="updateBulkBtn('mk')"></td>` : ''}
      <td>${i + 1}</td>
      <td><code style="font-size:.78rem;background:#eef2f7;padding:2px 6px;border-radius:4px">${mk.kode}</code></td>
      <td>${mk.nama}</td>
      <td style="text-align:center"><strong>${mk.sks}</strong></td>
      <td style="text-align:center">${mk.semester}</td>
      ${isAdmin ? `<td><div class="action-btns">
        <button class="btn-edit" onclick="editMatKul(${mk.id})">Edit</button>
        <button class="btn-del"  onclick="deleteMatKul(${mk.id}, '${mk.nama}')">Hapus</button>
      </div></td>` : ''}
    </tr>`).join('');

    const cbAll = document.getElementById('cb-all-mk');
    if (cbAll) cbAll.onchange = () => toggleAllCb('cb-mk', cbAll.checked, 'mk');

  } catch (e) {
    document.getElementById('tbl-mk-body').innerHTML =
      '<tr><td colspan="7" class="loading-cell">Gagal memuat data</td></tr>';
  }
}

function clearFormMk() {
  document.getElementById('modal-mk-title').textContent = 'Tambah Mata Kuliah';
  document.getElementById('mk-id').value       = '';
  document.getElementById('mk-kode').value     = '';
  document.getElementById('mk-nama').value     = '';
  document.getElementById('mk-sks').value      = '3';
  document.getElementById('mk-semester').value = '1';
}

async function editMatKul(id) {
  try {
    const res  = await fetch(`${API}/mata_kuliah.php?id=${id}`, { credentials: 'include' });
    const data = await res.json();
    if (data.error) { showToast(data.error, 'error'); return; }

    document.getElementById('modal-mk-title').textContent = 'Edit Mata Kuliah';
    document.getElementById('mk-id').value       = data.id;
    document.getElementById('mk-kode').value     = data.kode || '';
    document.getElementById('mk-nama').value     = data.nama || '';
    document.getElementById('mk-sks').value      = data.sks  || 3;
    document.getElementById('mk-semester').value = data.semester || 1;

    openModal('modal-mk');
  } catch (e) {
    showToast('Gagal memuat data', 'error');
  }
}

async function saveMatKul() {
  const id   = document.getElementById('mk-id').value;
  const kode = document.getElementById('mk-kode').value.trim();
  const nama = document.getElementById('mk-nama').value.trim();

  if (!kode || !nama) { showToast('Kode dan Nama wajib diisi', 'error'); return; }

  const payload = {
    kode, nama,
    sks:      parseInt(document.getElementById('mk-sks').value)      || 3,
    semester: parseInt(document.getElementById('mk-semester').value) || 1,
  };

  const isEdit = !!id;
  const url    = isEdit ? `${API}/mata_kuliah.php?id=${id}` : `${API}/mata_kuliah.php`;
  const method = isEdit ? 'PUT' : 'POST';

  try {
    const res  = await fetch(url, {
      method,
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
      showToast(isEdit ? 'Mata kuliah berhasil diperbarui' : 'Mata kuliah berhasil ditambahkan');
      closeModal('modal-mk');
      loadMatKul();
    } else showToast(data.error || 'Gagal menyimpan', 'error');
  } catch (e) {
    showToast('Terjadi kesalahan', 'error');
  }
}

async function deleteMatKul(id, nama) {
  if (!confirm(`Hapus mata kuliah "${nama}"?`)) return;
  try {
    const res  = await fetch(`${API}/mata_kuliah.php?id=${id}`, {
      method: 'DELETE', credentials: 'include'
    });
    const data = await res.json();
    if (data.success) { showToast('Mata kuliah berhasil dihapus'); loadMatKul(); }
    else showToast(data.error || 'Gagal menghapus', 'error');
  } catch (e) {
    showToast('Terjadi kesalahan', 'error');
  }
}

async function bulkDeleteMatKul() {
  const checked = [...document.querySelectorAll('.cb-mk:checked')];
  if (checked.length === 0) return;

  const ids = checked.map(c => parseInt(c.value));
  if (!confirm(`Hapus ${ids.length} mata kuliah yang dipilih?`)) return;

  try {
    const res  = await fetch(`${API}/mata_kuliah.php`, {
      method: 'DELETE',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ ids })
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message || `${ids.length} mata kuliah dihapus`);
      document.getElementById('btn-bulk-mk').style.display = 'none';
      document.getElementById('count-mk').textContent = '0';
      loadMatKul();
    } else showToast(data.error || 'Gagal menghapus', 'error');
  } catch (e) {
    showToast('Terjadi kesalahan', 'error');
  }
}

/* ═══════════════════════════════════════════
   GANTI PASSWORD
═══════════════════════════════════════════ */
async function changePassword() {
  const oldPw  = document.getElementById('old-password').value;
  const newPw  = document.getElementById('new-password').value;
  const confPw = document.getElementById('confirm-password').value;

  if (!oldPw || !newPw || !confPw) { showToast('Semua field wajib diisi', 'error'); return; }
  if (newPw.length < 6) { showToast('Password baru minimal 6 karakter', 'error'); return; }
  if (newPw !== confPw) { showToast('Konfirmasi password tidak cocok', 'error'); return; }

  try {
    const res  = await fetch(`${API}/change_password.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'include',
      body: JSON.stringify({ old_password: oldPw, new_password: newPw, confirm_password: confPw })
    });
    const data = await res.json();
    if (data.success) {
      showToast('Password berhasil diubah');
      closeModal('modal-password');
      ['old-password','new-password','confirm-password'].forEach(id => document.getElementById(id).value = '');
    } else {
      showToast(data.error || 'Gagal mengubah password', 'error');
    }
  } catch (e) {
    showToast('Terjadi kesalahan', 'error');
  }
}

/* ═══════════════════════════════════════════
   CHECKBOX BULK DELETE HELPERS
═══════════════════════════════════════════ */
function toggleAllCb(className, checked, group) {
  document.querySelectorAll(`.${className}`).forEach(cb => cb.checked = checked);
  updateBulkBtn(group);
}

function updateBulkBtn(group) {
  const checked = document.querySelectorAll(`.cb-${group}:checked`).length;
  const btn     = document.getElementById(`btn-bulk-${group}`);
  const count   = document.getElementById(`count-${group}`);
  if (!btn || !count) return;
  btn.style.display   = checked > 0 ? 'inline-flex' : 'none';
  count.textContent   = checked;

  // Sinkronkan "pilih semua"
  const all   = document.querySelectorAll(`.cb-${group}`).length;
  const cbAll = document.getElementById(`cb-all-${group}`);
  if (cbAll) cbAll.checked = checked === all && all > 0;
}

/* ═══════════════════════════════════════════
   FILTER / SEARCH TABLE
═══════════════════════════════════════════ */
function filterTable(tbodyId, query) {
  const q    = query.toLowerCase();
  const rows = document.getElementById(tbodyId)?.querySelectorAll('tr') || [];
  rows.forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

/* ═══════════════════════════════════════════
   MODAL HELPERS
═══════════════════════════════════════════ */
function openModal(id, clearFn) {
  if (clearFn) clearFn();
  document.getElementById(id).style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeModal(id) {
  document.getElementById(id).style.display = 'none';
  document.body.style.overflow = '';
}

// Tutup modal saat klik overlay
document.querySelectorAll('.modal-overlay').forEach(overlay => {
  overlay.addEventListener('click', e => {
    if (e.target === overlay) closeModal(overlay.id);
  });
});

/* ═══════════════════════════════════════════
   TOAST NOTIFICATION
═══════════════════════════════════════════ */
let toastTimer = null;

function showToast(msg, type = 'success') {
  const el = document.getElementById('toast');
  el.textContent  = (type === 'success' ? '✓ ' : '✕ ') + msg;
  el.className    = `toast ${type}`;
  el.style.display = 'block';

  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(() => { el.style.display = 'none'; }, 3200);
}