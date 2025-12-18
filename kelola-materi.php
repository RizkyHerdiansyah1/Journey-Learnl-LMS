<?php
require_once 'config.php';
requireRole('dosen');

$user_id = $_SESSION['user_id'];
$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;

// Cek apakah kelas ini milik dosen yang login
$query = "SELECT * FROM kelas WHERE id = ? AND dosen_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $kelas_id, $user_id);
$stmt->execute();
$kelas = $stmt->get_result()->fetch_assoc();

if (!$kelas) {
    setAlert('danger', 'Kelas tidak ditemukan atau bukan milik Anda!');
    header('Location: dashboard.php');
    exit;
}

// Proses hapus materi
if (isset($_GET['delete'])) {
    $materi_id = (int)$_GET['delete'];
    
    $query = "DELETE FROM materi WHERE id = ? AND kelas_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $materi_id, $kelas_id);
    
    if ($stmt->execute()) {
        // Re-order urutan materi
        $query = "SET @num := 0; UPDATE materi SET urutan = (@num := @num + 1) WHERE kelas_id = ? ORDER BY urutan";
        $conn->query("SET @num := 0");
        $stmt = $conn->prepare("UPDATE materi SET urutan = (@num := @num + 1) WHERE kelas_id = ? ORDER BY urutan");
        $stmt->bind_param("i", $kelas_id);
        $stmt->execute();
        
        setAlert('success', 'Materi berhasil dihapus!');
    } else {
        setAlert('danger', 'Gagal menghapus materi!');
    }
    
    header('Location: kelola-materi.php?kelas_id=' . $kelas_id);
    exit;
}

// Get semua materi
$query = "SELECT * FROM materi WHERE kelas_id = ? ORDER BY urutan ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $kelas_id);
$stmt->execute();
$materi_list = $stmt->get_result();

// Get statistik
$query = "SELECT COUNT(*) as total_mahasiswa FROM enrollment WHERE kelas_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $kelas_id);
$stmt->execute();
$stat = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Materi - <?= htmlspecialchars($kelas['nama_kelas']) ?></title>
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
        .header-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .materi-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .materi-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
        }
        .materi-icon.video { background: rgba(102, 126, 234, 0.1); }
        .materi-icon.quiz { background: rgba(255, 159, 64, 0.1); }
        .materi-icon.text { background: rgba(56, 239, 125, 0.1); }
        .drag-handle {
            cursor: move;
            color: #999;
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
        
        <!-- Header -->
        <div class="header-card">
            <h2 class="mb-3"><?= htmlspecialchars($kelas['nama_kelas']) ?></h2>
            <p class="mb-4"><?= htmlspecialchars($kelas['deskripsi']) ?></p>
            <div class="d-flex gap-4">
                <div>
                    <strong>👥 Mahasiswa:</strong> <?= $stat['total_mahasiswa'] ?>
                </div>
                <div>
                    <strong>📚 Total Materi:</strong> <?= $materi_list->num_rows ?>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4>Daftar Materi</h4>
            <a href="tambah-materi.php?kelas_id=<?= $kelas_id ?>" class="btn btn-primary">
                + Tambah Materi Baru
            </a>
        </div>
        
        <!-- Materi List -->
        <?php if ($materi_list->num_rows > 0): ?>
            <?php while ($materi = $materi_list->fetch_assoc()): 
                $icon_type = $materi['tipe'];
                $icon = $icon_type == 'video' ? '🎥' : ($icon_type == 'quiz' ? '📝' : '📄');
            ?>
                <div class="materi-card">
                    <div class="drag-handle">☰</div>
                    
                    <div class="materi-icon <?= $icon_type ?>">
                        <?= $icon ?>
                    </div>
                    
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                            <h6 class="mb-0">
                                <span class="badge bg-secondary">#<?= $materi['urutan'] ?></span>
                                <?= htmlspecialchars($materi['judul']) ?>
                            </h6>
                            <span class="badge bg-primary"><?= ucfirst($materi['tipe']) ?></span>
                        </div>
                        <small class="text-muted">
                            Durasi: <?= $materi['durasi_menit'] ?> menit • 
                            Dibuat: <?= date('d M Y', strtotime($materi['tanggal_dibuat'])) ?>
                        </small>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <a href="edit-materi.php?id=<?= $materi['id'] ?>" class="btn btn-sm btn-warning">
                            ✏️ Edit
                        </a>
                        <a href="kelola-materi.php?kelas_id=<?= $kelas_id ?>&delete=<?= $materi['id'] ?>" 
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Yakin ingin menghapus materi ini?')">
                            🗑️ Hapus
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="alert alert-info">
                Belum ada materi. <a href="tambah-materi.php?kelas_id=<?= $kelas_id ?>" class="alert-link">Tambah materi pertama!</a>
            </div>
        <?php endif; ?>
        
        <!-- Info Card -->
        <div class="card mt-4">
            <div class="card-body">
                <h6 class="card-title">💡 Tips Mengelola Materi:</h6>
                <ul class="mb-0 small">
                    <li>Susun materi secara berurutan dari mudah ke sulit</li>
                    <li>Gunakan video untuk penjelasan visual</li>
                    <li>Tambahkan quiz untuk mengetes pemahaman</li>
                    <li>Materi akan terkunci secara otomatis (sequential learning)</li>
                </ul>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>