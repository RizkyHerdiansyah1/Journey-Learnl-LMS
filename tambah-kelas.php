<?php
require_once 'config.php';
requireRole('dosen');

$user_id = $_SESSION['user_id'];

// Proses submit
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_kelas = cleanInput($_POST['nama_kelas']);
    $deskripsi = cleanInput($_POST['deskripsi']);
    $kategori = cleanInput($_POST['kategori']);
    
    if (empty($nama_kelas)) {
        setAlert('danger', 'Nama kelas harus diisi!');
    } elseif (empty($deskripsi)) {
        setAlert('danger', 'Deskripsi harus diisi!');
    } else {
        $query = "INSERT INTO kelas (nama_kelas, deskripsi, dosen_id, kategori, total_courses) 
                  VALUES (?, ?, ?, ?, 0)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssis", $nama_kelas, $deskripsi, $user_id, $kategori);
        
        if ($stmt->execute()) {
            $kelas_id = $conn->insert_id;
            setAlert('success', 'Kelas berhasil dibuat! Sekarang tambahkan materi.');
            header('Location: kelola-materi.php?kelas_id=' . $kelas_id);
            exit;
        } else {
            setAlert('danger', 'Gagal membuat kelas!');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Kelas - Journey Learn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }
        .navbar {
            background: white !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-card {
            background: white;
            border-radius: 15px;
            padding: 40px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            max-width: 800px;
            margin: 0 auto;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">🎓 Journey Learn</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php showAlert(); ?>
        
        <a href="dashboard.php" class="btn btn-outline-primary mb-3">
            ← Kembali ke Dashboard
        </a>
        
        <div class="form-card">
            <h3 class="mb-4">➕ Buat Kelas Baru</h3>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="form-label">Nama Kelas <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="nama_kelas" 
                           placeholder="Contoh: Introduction to Web Development" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Deskripsi <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="deskripsi" rows="5" 
                              placeholder="Jelaskan tentang kelas ini, apa yang akan dipelajari, untuk siapa kelas ini, dll." 
                              required></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                    <select class="form-select" name="kategori" required>
                        <option value="">Pilih Kategori</option>
                        <option value="Web Development">Web Development</option>
                        <option value="Mobile Development">Mobile Development</option>
                        <option value="Data Science">Data Science</option>
                        <option value="Machine Learning">Machine Learning</option>
                        <option value="Database">Database</option>
                        <option value="Programming">Programming</option>
                        <option value="Design">Design</option>
                        <option value="Business">Business</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="alert alert-info">
                    <strong>📌 Info:</strong> Setelah membuat kelas, Anda akan diarahkan untuk menambahkan materi pembelajaran.
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                        🚀 Buat Kelas
                    </button>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-lg">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>