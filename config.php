<?php
// ================================================
// CONFIG.PHP - Database Connection & Settings
// ================================================

session_start();

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Kosongkan jika default XAMPP
define('DB_NAME', 'db_elearning');

// Koneksi Database menggunakan MySQLi
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Set charset ke UTF-8
$conn->set_charset("utf8mb4");

// Base URL (sesuaikan dengan folder project Anda)
define('BASE_URL', 'http://localhost/lms/');

// Upload Directory
define('UPLOAD_DIR', 'uploads/');

// ================================================
// HELPER FUNCTIONS
// ================================================

// Fungsi untuk cek apakah user sudah login
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

// Fungsi untuk cek role user
function isRole($role)
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Fungsi redirect jika belum login
function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit;
    }
}

// Fungsi redirect jika bukan role tertentu
function requireRole($role)
{
    requireLogin();
    if (!isRole($role)) {
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit;
    }
}

// Fungsi sanitize input
function cleanInput($data)
{
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

// Fungsi untuk hash password
function hashPassword($password)
{
    return password_hash($password, PASSWORD_DEFAULT);
}

// Fungsi untuk verify password
function verifyPassword($password, $hash)
{
    return password_verify($password, $hash);
}

// Fungsi untuk generate alert message
function setAlert($type, $message)
{
    $_SESSION['alert'] = [
        'type' => $type, // success, danger, warning, info
        'message' => $message
    ];
}

// Fungsi untuk display alert
function showAlert()
{
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        echo '<div class="alert alert-' . $alert['type'] . ' alert-dismissible fade show" role="alert">
                ' . $alert['message'] . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
              </div>';
        unset($_SESSION['alert']);
    }
}

// Fungsi untuk format tanggal Indonesia
function formatTanggal($date)
{
    $bulan = [
        1 => 'Januari',
        'Februari',
        'Maret',
        'April',
        'Mei',
        'Juni',
        'Juli',
        'Agustus',
        'September',
        'Oktober',
        'November',
        'Desember'
    ];
    $timestamp = strtotime($date);
    return date('d', $timestamp) . ' ' . $bulan[date('n', $timestamp)] . ' ' . date('Y', $timestamp);
}

// Fungsi untuk get user data
function getUserData($user_id)
{
    global $conn;
    $query = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Fungsi untuk cek akses materi (sequential learning)
function canAccessMateri($user_id, $materi_id)
{
    global $conn;
    // Get urutan materi saat ini
    $query = "SELECT kelas_id, urutan FROM materi WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $materi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $materi = $result->fetch_assoc();
    if (!$materi)
        return false;
    // Jika urutan = 1, bisa langsung diakses
    if ($materi['urutan'] == 1)
        return true;
    // 🔥 FIX: Cek bahwa SEMUA materi sebelumnya sudah completed
    // Hitung total materi yang harus diselesaikan
    $totalMateriSebelum = $materi['urutan'] - 1;

    // Hitung berapa materi yang sudah completed
    $query = "SELECT COUNT(*) as completed_count 
              FROM progress p
              JOIN materi m ON p.materi_id = m.id
              WHERE p.user_id = ? 
              AND m.kelas_id = ? 
              AND m.urutan < ?
              AND p.status = 'completed'";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $user_id, $materi['kelas_id'], $materi['urutan']);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // ✅ HARUS SAMA PERSIS: completed_count == total yang diperlukan
    return $row['completed_count'] == $totalMateriSebelum;
}

// Fungsi untuk update progress materi
function updateProgress($user_id, $materi_id, $status)
{
    global $conn;

    // Cek apakah progress sudah ada
    $query = "SELECT id FROM progress WHERE user_id = ? AND materi_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $materi_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing progress
        $query = "UPDATE progress SET status = ?, 
                  tanggal_selesai = CASE WHEN ? = 'completed' THEN NOW() ELSE tanggal_selesai END
                  WHERE user_id = ? AND materi_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssii", $status, $status, $user_id, $materi_id);
    } else {
        // Insert new progress
        $query = "INSERT INTO progress (user_id, materi_id, status, tanggal_mulai) 
                  VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iis", $user_id, $materi_id, $status);
    }

    return $stmt->execute();
}

// ================================================
// ERROR HANDLING
// ================================================

// Custom error handler
function customError($errno, $errstr, $errfile, $errline)
{
    error_log("Error [$errno]: $errstr in $errfile on line $errline");
    // Jangan tampilkan error detail ke user di production
}

set_error_handler("customError");

// ================================================
// TIMEZONE
// ================================================
date_default_timezone_set('Asia/Jakarta');

?>