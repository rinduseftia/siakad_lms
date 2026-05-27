<?php
// Mengatur agar output selalu berupa JSON
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Sesi untuk validasi login/role
session_start();

// ── KONEKSI DATABASE ──
// Sesuaikan "nama_database_kamu" dengan nama database asli di MySQL-mu
$host = "localhost";
$user = "root";
$pass = "";
$db   = "nama_database_kamu";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Koneksi database gagal: " . $conn->connect_error]));
}

// Deteksi metode HTTP (GET, POST, atau DELETE)
$method = $_SERVER['REQUEST_METHOD'];

// -------------------------------------------------------------------------
// 1. METODE GET: Mengambil Seluruh Data (Dicall saat halaman pertama dimuat)
// -------------------------------------------------------------------------
if ($method === 'GET') {
    // Query mengambil ke-11 field sesuai dengan kolom di tabel HTML kamu
    $query = "SELECT nim, nama, jur, prodi, email, agama, status, jk, tmp_lahir, tgl_lahir, alamat FROM tabel_mahasiswa ORDER BY nim ASC";
    $result = $conn->query($query);
    
    $data = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
    }
    
    // Kirim balik ke HTML dalam bentuk JSON array
    echo json_encode($data);
    exit();
}

// -------------------------------------------------------------------------
// 2. METODE POST: Menangani Tambah Baru (Add) dan Pembaruan Data (Edit)
// -------------------------------------------------------------------------
if ($method === 'POST') {
    // Membaca data JSON yang dikirim oleh JavaScript fetch body
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(["success" => false, "message" => "Payload data tidak terbaca oleh server."]);
        exit();
    }

    // Ekstraksi data & bersihkan dari karakter berbahaya (SQL Injection Protection)
    $action    = $input['action']; // 'add' atau 'edit'
    $nim       = $conn->real_escape_string($input['nim']);
    $nama      = $conn->real_escape_string($input['nama']);
    $jur       = $conn->real_escape_string($input['jur']);
    $prodi     = $conn->real_escape_string($input['prodi']);
    $email     = $conn->real_escape_string($input['email']);
    $agama     = $conn->real_escape_string($input['agama']);
    $status    = $conn->real_escape_string($input['status']);
    $jk        = $conn->real_escape_string($input['jk']);
    $tmp_lahir = $conn->real_escape_string($input['tmp_lahir']);
    $tgl_lahir = $conn->real_escape_string($input['tgl_lahir']);
    $alamat    = $conn->real_escape_string($input['alamat']);

    // A. JIKA AKSI TAMBAH DATA MAHASISWA
    if ($action === 'add') {
        $query = "INSERT INTO tabel_mahasiswa (nim, nama, jur, prodi, email, agama, status, jk, tmp_lahir, tgl_lahir, alamat) 
                  VALUES ('$nim', '$nama', '$jur', '$prodi', '$email', '$agama', '$status', '$jk', '$tmp_lahir', '$tgl_lahir', '$alamat')";
        
        if ($conn->query($query)) {
            echo json_encode(["success" => true, "message" => "Mahasiswa [$nama] berhasil didaftarkan ke sistem!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Gagal input database: " . $conn->error]);
        }
    } 
    // B. JIKA AKSI EDIT DATA MAHASISWA
    elseif ($action === 'edit') {
        $query = "UPDATE tabel_mahasiswa SET 
                    nama = '$nama', 
                    jur = '$jur', 
                    prodi = '$prodi', 
                    email = '$email', 
                    agama = '$agama', 
                    status = '$status', 
                    jk = '$jk', 
                    tmp_lahir = '$tmp_lahir', 
                    tgl_lahir = '$tgl_lahir', 
                    alamat = '$alamat' 
                  WHERE nim = '$nim'";
        
        if ($conn->query($query)) {
            echo json_encode(["success" => true, "message" => "Data Mahasiswa NIM $nim Berhasil diperbarui!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Gagal update database: " . $conn->error]);
        }
    }
    exit();
}

// -------------------------------------------------------------------------
// 3. METODE DELETE: Menghapus Data Mahasiswa Berdasarkan NIM tunggal
// -------------------------------------------------------------------------
if ($method === 'DELETE') {
    // Mengambil parameter '?nim=...' dari URL endpoint
    if (isset($_GET['nim'])) {
        $nim = $conn->real_escape_string($_GET['nim']);
        $query = "DELETE FROM tabel_mahasiswa WHERE nim = '$nim'";
        
        if ($conn->query($query)) {
            echo json_encode(["success" => true, "message" => "Mahasiswa dengan NIM $nim telah berhasil dihapus."]);
        } else {
            echo json_encode(["success" => false, "message" => "Gagal menghapus dari database: " . $conn->error]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Parameter NIM kosong / tidak ditemukan."]);
    }
    exit();
}

$conn->close();
?>