// assets/app.js - SIAKAD Frontend JavaScript
// Menggunakan Fetch API untuk komunikasi REST API dengan backend PHP

const BASE = "http://localhost/siakad/api"; // sesuaikan dengan nama folder di htdocs

// ============================================================
// UTILITAS
// ============================================================

function showToast(msg, type = "success") {
  const t = document.getElementById("toast");
  t.textContent = msg;
  t.className = "toast " + type;
  t.style.display = "block";
  setTimeout(() => (t.style.display = "none"), 3000);
}

function openModal(id, clearFn) {
  if (clearFn) clearFn();
  document.getElementById(id).style.display = "flex";
}

function closeModal(id) {
  document.getElementById(id).style.display = "none";
}

function filterTable(tableId, query) {
  const rows = document.querySelectorAll("#" + tableId + " tbody tr");
  const q = query.toLowerCase();
  rows.forEach((row) => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? "" : "none";
  });
}

function showPage(name, el) {
  document
    .querySelectorAll(".page")
    .forEach((p) => p.classList.remove("active"));
  document
    .querySelectorAll(".nav-item")
    .forEach((n) => n.classList.remove("active"));
  document.getElementById("page-" + name).classList.add("active");
  if (el && el.classList) el.classList.add("active");
  const titles = {
    dashboard: "Dashboard",
    mahasiswa: "Data Mahasiswa",
    dosen: "Data Dosen",
    matkul: "Data Mata Kuliah",
  };
  document.getElementById("page-heading").textContent = titles[name] || "";

  // Muat data saat halaman dibuka
  if (name === "dashboard") loadDashboard();
  if (name === "mahasiswa") loadMahasiswa();
  if (name === "dosen") loadDosen();
  if (name === "matkul") loadMatKul();
}

// ============================================================
// LOGIN / LOGOUT
// ============================================================

async function doLogin() {
  const username = document.getElementById("username").value.trim();
  const password = document.getElementById("password").value;
  const errEl = document.getElementById("login-error");

  if (!username || !password) {
    errEl.textContent = "Username dan password wajib diisi";
    errEl.style.display = "block";
    return;
  }

  try {
    const res = await fetch(BASE + "/login.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ username, password }),
    });
    const data = await res.json();

    if (data.success) {
      errEl.style.display = "none";
      document.getElementById("login-page").style.display = "none";
      document.getElementById("main-app").style.display = "flex";
      document.getElementById("admin-name").textContent = data.admin.nama;
      document.getElementById("welcome-name").textContent = data.admin.nama;
      loadDashboard();
    } else {
      errEl.textContent = data.error || "Login gagal";
      errEl.style.display = "block";
    }
  } catch (err) {
    // Mode offline / tanpa backend: langsung masuk untuk demo
    document.getElementById("login-page").style.display = "none";
    document.getElementById("main-app").style.display = "flex";
    loadDashboard();
  }
}

// Tekan Enter di form login
document.addEventListener("DOMContentLoaded", () => {
  document.getElementById("password").addEventListener("keydown", (e) => {
    if (e.key === "Enter") doLogin();
  });
});

function doLogout() {
  if (confirm("Yakin ingin logout?")) {
    document.getElementById("main-app").style.display = "none";
    document.getElementById("login-page").style.display = "flex";
    document.getElementById("username").value = "";
    document.getElementById("password").value = "";
  }
}

// ============================================================
// DASHBOARD
// ============================================================

async function loadDashboard() {
  try {
    const res = await fetch(BASE + "/dashboard.php");
    const data = await res.json();

    document.getElementById("stat-mhs").textContent = data.total_mahasiswa;
    document.getElementById("stat-dsn").textContent = data.total_dosen;
    document.getElementById("stat-mk").textContent = data.total_matkul;

    document.getElementById("tbl-mhs-terbaru").innerHTML =
      (data.mahasiswa_terbaru || [])
        .map((m) => `<tr><td>${m.nim}</td><td>${m.nama}</td></tr>`)
        .join("") || '<tr><td colspan="2">Belum ada data</td></tr>';

    document.getElementById("tbl-dsn-terbaru").innerHTML =
      (data.dosen_terbaru || [])
        .map((d) => `<tr><td>${d.nidn}</td><td>${d.nama}</td></tr>`)
        .join("") || '<tr><td colspan="2">Belum ada data</td></tr>';

    document.getElementById("tbl-mk-terbaru").innerHTML =
      (data.matkul_terbaru || [])
        .map((mk) => `<tr><td>${mk.kode}</td><td>${mk.nama}</td></tr>`)
        .join("") || '<tr><td colspan="2">Belum ada data</td></tr>';
  } catch (err) {
    // Tampilkan data statis saat offline
    document.getElementById("stat-mhs").textContent = "4";
    document.getElementById("stat-dsn").textContent = "2";
    document.getElementById("stat-mk").textContent = "2";
  }
}

// ============================================================
// MAHASISWA
// ============================================================

async function loadMahasiswa() {
  try {
    const res = await fetch(BASE + "/mahasiswa.php");
    const list = await res.json();
    const tbody = document.getElementById("tbl-mhs-body");
    if (!list.length) {
      tbody.innerHTML =
        '<tr><td colspan="13" style="text-align:center">Belum ada data</td></tr>';
      return;
    }
    tbody.innerHTML = list
      .map(
        (m, i) => `
      <tr>
        <td>${i + 1}</td>
        <td>${m.nim}</td>
        <td><strong>${m.nama}</strong></td>
        <td>${m.jur || "-"}</td>
        <td>${m.program_studi || "-"}</td>
        <td>${m.email || "-"}</td>
        <td>${m.agama || "-"}</td>
        <td>${m.status || "-"}</td>
        <td>${m.jk || "-"}</td>
        <td>${m.tmp_lahir || "-"}</td>
        <td>${m.tgl_lahir || "-"}</td>
        <td>${m.alamat || "-"}</td>
        <td>
          <button
            class="btn btn-warning btn-sm"
            onclick="editMahasiswa(${m.id})">
            Edit
          </button>
          <button
            class="btn btn-danger btn-sm"
            onclick="deleteMahasiswa(${m.id}, '${m.nama}')">
            Hapus
          </button>
        </td>
      </tr>
      `,).join("");
  } catch (err) {
    document.getElementById("tbl-mhs-body").innerHTML =
      '<tr><td colspan="13" style="color:red">Gagal memuat data</td></tr>';
  }
}

function clearFormMhs() {
  document.getElementById("mhs-id").value = "";
  document.getElementById("mhs-nim").value = "";
  document.getElementById("mhs-nama").value = "";
  document.getElementById("mhs-jur").value = "";
  document.getElementById("mhs-program_studi").value = "";
  document.getElementById("mhs-email").value = "";
  document.getElementById("mhs-agama").value = "Islam";
  document.getElementById("mhs-status").value = "Aktif";
  document.getElementById("mhs-jk").value = "Laki-laki";
  document.getElementById("mhs-tmp-lahir").value = "";
  document.getElementById("mhs-tgl-lahir").value = "";
  document.getElementById("mhs-alamat").value = "";
  document.getElementById("modal-mhs-title").textContent = "Tambah Mahasiswa";
}

async function editMahasiswa(id) {
  const res = await fetch(`${BASE}/mahasiswa.php?id=${id}`);
  const m = await res.json();
  document.getElementById("mhs-id").value = m.id;
  document.getElementById("mhs-nim").value = m.nim;
  document.getElementById("mhs-nama").value = m.nama;
  document.getElementById("mhs-jur").value = m.jur || "";
  document.getElementById("mhs-program_studi").value = m.program_studi || "";
  document.getElementById("mhs-email").value = m.email || "";
  document.getElementById("mhs-agama").value = m.agama || "Islam";
  document.getElementById("mhs-status").value = m.status || "Aktif";
  document.getElementById("mhs-jk").value = m.jk || "Laki-laki";
  document.getElementById("mhs-tmp-lahir").value = m.tmp_lahir || "";
  document.getElementById("mhs-tgl-lahir").value = m.tgl_lahir || "";
  document.getElementById("mhs-alamat").value = m.alamat || "";
  document.getElementById("modal-mhs-title").textContent = "Edit Mahasiswa";
  openModal("modal-mhs", null);
}

async function saveMahasiswa() {
  const id = document.getElementById("mhs-id").value;
  const body = {
    nim: document.getElementById("mhs-nim").value.trim(),
    nama: document.getElementById("mhs-nama").value.trim(),
    jur: document.getElementById("mhs-jur").value,
    program_studi: document.getElementById("mhs-program_studi").value,
    email: document.getElementById("mhs-email").value.trim(),
    agama: document.getElementById("mhs-agama").value,
    status: document.getElementById("mhs-status").value,
    jk: document.getElementById("mhs-jk").value,
    tmp_lahir: document.getElementById("mhs-tmp-lahir").value.trim(),
    tgl_lahir: document.getElementById("mhs-tgl-lahir").value,
    alamat: document.getElementById("mhs-alamat").value.trim(),
  };
  if (!body.nim || !body.nama) {
    showToast("NIM dan Nama wajib diisi!", "error");
    return;
  }
  const method = id ? "PUT" : "POST";
  const url = id ? `${BASE}/mahasiswa.php?id=${id}` : `${BASE}/mahasiswa.php`;
  try {
    const res = await fetch(url, {
      method,
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message);
      closeModal("modal-mhs");
      loadMahasiswa();
      loadDashboard();
    } else {
      showToast(data.error || "Gagal menyimpan", "error");
    }
  } catch (err) {
    showToast("Koneksi ke server gagal", "error");
  }
}

async function deleteMahasiswa(id, nama) {
  if (!confirm(`Hapus mahasiswa "${nama}"?`)) return;
  const res = await fetch(`${BASE}/mahasiswa.php?id=${id}`, {
    method: "DELETE",
  });
  const data = await res.json();
  if (data.success) {
    showToast(data.message);
    loadMahasiswa();
    loadDashboard();
  } else {
    showToast(data.error || "Gagal menghapus", "error");
  }
}

// ============================================================
// DOSEN
// ============================================================

async function loadDosen() {
  try {
    const res = await fetch(BASE + "/dosen.php");
    const list = await res.json();
    const tbody = document.getElementById("tbl-dsn-body");
    if (!list.length) {
      tbody.innerHTML =
        '<tr><td colspan="6" style="text-align:center">Belum ada data</td></tr>';
      return;
    }
    tbody.innerHTML = list
      .map(
        (d, i) => `
      <tr>
        <td>${i + 1}</td>
        <td>${d.nidn}</td>
        <td><strong>${d.nama}</strong></td>
        <td>${d.email || "–"}</td>
        <td><span class="badge badge-${d.status === "Aktif" ? "aktif" : "nonaktif"}">${d.status}</span></td>
        <td>
          <button class="btn btn-warning btn-sm" onclick="editDosen(${d.id})">Edit</button>
          <button class="btn btn-danger btn-sm"  onclick="deleteDosen(${d.id}, '${d.nama}')">Hapus</button>
        </td>
      </tr>
    `,
      )
      .join("");
  } catch (err) {
    document.getElementById("tbl-dsn-body").innerHTML =
      '<tr><td colspan="6" style="color:red">Gagal memuat data. Pastikan XAMPP aktif.</td></tr>';
  }
}

function clearFormDsn() {
  ["dsn-id", "dsn-nidn", "dsn-nama", "dsn-email", "dsn-hp"].forEach(
    (id) => (document.getElementById(id).value = ""),
  );
  document.getElementById("dsn-status").value = "Aktif";
  document.getElementById("modal-dsn-title").textContent = "Tambah Dosen";
}

async function editDosen(id) {
  const res = await fetch(`${BASE}/dosen.php?id=${id}`);
  const d = await res.json();
  document.getElementById("dsn-id").value = d.id;
  document.getElementById("dsn-nidn").value = d.nidn;
  document.getElementById("dsn-nama").value = d.nama;
  document.getElementById("dsn-email").value = d.email || "";
  document.getElementById("dsn-hp").value = d.no_hp || "";
  document.getElementById("dsn-status").value = d.status;
  document.getElementById("modal-dsn-title").textContent = "Edit Dosen";
  openModal("modal-dsn", null);
}

async function saveDosen() {
  const id = document.getElementById("dsn-id").value;
  const body = {
    nidn: document.getElementById("dsn-nidn").value.trim(),
    nama: document.getElementById("dsn-nama").value.trim(),
    email: document.getElementById("dsn-email").value.trim(),
    no_hp: document.getElementById("dsn-hp").value.trim(),
    status: document.getElementById("dsn-status").value,
  };
  if (!body.nidn || !body.nama) {
    showToast("NIDN dan Nama wajib diisi!", "error");
    return;
  }
  const method = id ? "PUT" : "POST";
  const url = id ? `${BASE}/dosen.php?id=${id}` : `${BASE}/dosen.php`;
  try {
    const res = await fetch(url, {
      method,
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message);
      closeModal("modal-dsn");
      loadDosen();
      loadDashboard();
    } else {
      showToast(data.error || "Gagal menyimpan", "error");
    }
  } catch (err) {
    showToast("Koneksi ke server gagal", "error");
  }
}

async function deleteDosen(id, nama) {
  if (!confirm(`Hapus dosen "${nama}"?`)) return;
  const res = await fetch(`${BASE}/dosen.php?id=${id}`, { method: "DELETE" });
  const data = await res.json();
  if (data.success) {
    showToast(data.message);
    loadDosen();
    loadDashboard();
  } else {
    showToast(data.error || "Gagal menghapus", "error");
  }
}

// ============================================================
// MATA KULIAH
// ============================================================

async function loadMatKul() {
  try {
    const res = await fetch(BASE + "/mata_kuliah.php");
    const list = await res.json();
    const tbody = document.getElementById("tbl-mk-body");
    if (!list.length) {
      tbody.innerHTML =
        '<tr><td colspan="6" style="text-align:center">Belum ada data</td></tr>';
      return;
    }
    tbody.innerHTML = list
      .map(
        (mk, i) => `
      <tr>
        <td>${i + 1}</td>
        <td><strong>${mk.kode}</strong></td>
        <td>${mk.nama}</td>
        <td>${mk.sks} SKS</td>
        <td>Semester ${mk.semester}</td>
        <td>
          <button class="btn btn-warning btn-sm" onclick="editMatKul(${mk.id})">Edit</button>
          <button class="btn btn-danger btn-sm"  onclick="deleteMatKul(${mk.id}, '${mk.nama}')">Hapus</button>
        </td>
      </tr>
    `,
      )
      .join("");
  } catch (err) {
    document.getElementById("tbl-mk-body").innerHTML =
      '<tr><td colspan="6" style="color:red">Gagal memuat data. Pastikan XAMPP aktif.</td></tr>';
  }
}

function clearFormMk() {
  ["mk-id", "mk-kode", "mk-nama"].forEach(
    (id) => (document.getElementById(id).value = ""),
  );
  document.getElementById("mk-sks").value = 3;
  document.getElementById("mk-semester").value = 1;
  document.getElementById("modal-mk-title").textContent = "Tambah Mata Kuliah";
}

async function editMatKul(id) {
  const res = await fetch(`${BASE}/mata_kuliah.php?id=${id}`);
  const mk = await res.json();
  document.getElementById("mk-id").value = mk.id;
  document.getElementById("mk-kode").value = mk.kode;
  document.getElementById("mk-nama").value = mk.nama;
  document.getElementById("mk-sks").value = mk.sks;
  document.getElementById("mk-semester").value = mk.semester;
  document.getElementById("modal-mk-title").textContent = "Edit Mata Kuliah";
  openModal("modal-mk", null);
}

async function saveMatKul() {
  const id = document.getElementById("mk-id").value;
  const body = {
    kode: document.getElementById("mk-kode").value.trim(),
    nama: document.getElementById("mk-nama").value.trim(),
    sks: parseInt(document.getElementById("mk-sks").value) || 3,
    semester: parseInt(document.getElementById("mk-semester").value) || 1,
  };
  if (!body.kode || !body.nama) {
    showToast("Kode dan Nama wajib diisi!", "error");
    return;
  }
  const method = id ? "PUT" : "POST";
  const url = id
    ? `${BASE}/mata_kuliah.php?id=${id}`
    : `${BASE}/mata_kuliah.php`;
  try {
    const res = await fetch(url, {
      method,
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(body),
    });
    const data = await res.json();
    if (data.success) {
      showToast(data.message);
      closeModal("modal-mk");
      loadMatKul();
      loadDashboard();
    } else {
      showToast(data.error || "Gagal menyimpan", "error");
    }
  } catch (err) {
    showToast("Koneksi ke server gagal", "error");
  }
}

async function deleteMatKul(id, nama) {
  if (!confirm(`Hapus mata kuliah "${nama}"?`)) return;
  const res = await fetch(`${BASE}/mata_kuliah.php?id=${id}`, {
    method: "DELETE",
  });
  const data = await res.json();
  if (data.success) {
    showToast(data.message);
    loadMatKul();
    loadDashboard();
  } else {
    showToast(data.error || "Gagal menghapus", "error");
  }
}

async function changePassword() {
  const oldPassword = document.getElementById("old-password").value;

  const newPassword = document.getElementById("new-password").value;

  const confirmPassword = document.getElementById("confirm-password").value;

  if (!oldPassword || !newPassword || !confirmPassword) {
    showToast("Semua field wajib diisi", "error");
    return;
  }

  if (newPassword !== confirmPassword) {
    showToast("Konfirmasi password tidak cocok", "error");
    return;
  }

  try {
    const response = await fetch(BASE + "/change_password.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        old_password: oldPassword,
        new_password: newPassword,
      }),
    });

    const result = await response.json();

    if (result.success) {
      showToast(result.message, "success");

      closeModal("modal-password");

      document.getElementById("old-password").value = "";
      document.getElementById("new-password").value = "";
      document.getElementById("confirm-password").value = "";
    } else {
      showToast(result.error, "error");
    }
  } catch (err) {
    showToast("Terjadi kesalahan", "error");
  }
}
