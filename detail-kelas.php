<?php
require_once 'config.php';
requireRole('mahasiswa');

$user_id = $_SESSION['user_id'];
$kelas_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Cek apakah mahasiswa sudah enroll kelas ini
$query = "SELECT * FROM enrollment WHERE user_id = ? AND kelas_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $kelas_id);
$stmt->execute();
$enrollment = $stmt->get_result();

if ($enrollment->num_rows == 0) {
    setAlert('danger', 'Anda belum enroll kelas ini!');
    header('Location: browse-kelas.php');
    exit;
}

// Get detail kelas
$query = "SELECT k.*, u.nama as nama_dosen 
          FROM kelas k 
          JOIN users u ON k.dosen_id = u.id 
          WHERE k.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $kelas_id);
$stmt->execute();
$result = $stmt->get_result();
$kelas = $result->fetch_assoc();

if (!$kelas) {
    setAlert('danger', 'Kelas tidak ditemukan!');
    header('Location: dashboard.php');
    exit;
}

// Get semua materi dengan progress
$query = "SELECT m.*, 
          COALESCE(p.status, 'locked') as progress_status,
          CASE 
              WHEN m.urutan = 1 THEN 1
              WHEN EXISTS (
                  SELECT 1 FROM progress p2 
                  JOIN materi m2 ON p2.materi_id = m2.id 
                  WHERE p2.user_id = ? 
                  AND m2.kelas_id = m.kelas_id 
                  AND m2.urutan < m.urutan 
                  AND p2.status = 'completed'
                  HAVING COUNT(*) = m.urutan - 1
              ) THEN 1
              ELSE 0
          END as can_access
          FROM materi m
          LEFT JOIN progress p ON m.id = p.materi_id AND p.user_id = ?
          WHERE m.kelas_id = ?
          ORDER BY m.urutan ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $user_id, $user_id, $kelas_id);
$stmt->execute();
$materi_list = $stmt->get_result();

// Hitung progress
$total_materi = $materi_list->num_rows;
$completed_count = 0;
$materi_array = [];
while ($m = $materi_list->fetch_assoc()) {
    $materi_array[] = $m;
    if ($m['progress_status'] == 'completed') {
        $completed_count++;
    }
}
$progress_percentage = $total_materi > 0 ? round(($completed_count / $total_materi) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($kelas['nama_kelas']) ?> - Journey Learn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .navbar {
            background: white !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .class-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 60px 0;
            border-radius: 20px;
            margin-bottom: 30px;
        }

        .progress-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 30px;
        }

        .materi-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            align-items: center;
            gap: 20px;
            transition: transform 0.2s;
        }

        .materi-item:hover {
            transform: translateX(5px);
        }

        .materi-item.locked {
            opacity: 0.5;
            cursor: not-allowed;
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

        .materi-icon.video {
            background: rgba(102, 126, 234, 0.1);
        }

        .materi-icon.quiz {
            background: rgba(255, 159, 64, 0.1);
        }

        .materi-icon.text {
            background: rgba(56, 239, 125, 0.1);
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-completed {
            background: #d4edda;
            color: #155724;
        }

        .status-progress {
            background: #fff3cd;
            color: #856404;
        }

        .status-locked {
            background: #f8d7da;
            color: #721c24;
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
                    <li class="nav-item"><a class="nav-link" href="browse-kelas.php">Jelajah Kelas</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php showAlert(); ?>

        <!-- Class Hero -->
        <div class="class-hero">
            <div class="container">
                <a href="dashboard.php" class="text-white text-decoration-none mb-3 d-inline-block">
                    ← Kembali ke Dashboard
                </a>
                <h1 class="mb-3"><?= htmlspecialchars($kelas['nama_kelas']) ?></h1>
                <p class="mb-4"><?= htmlspecialchars($kelas['deskripsi']) ?></p>
                <div class="d-flex gap-4">
                    <span>👨‍🏫 <?= htmlspecialchars($kelas['nama_dosen']) ?></span>
                    <span>📚 <?= $total_materi ?> Materi</span>
                    <span>🏷️ <?= htmlspecialchars($kelas['kategori']) ?></span>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Materi List -->
                <h4 class="mb-3">Daftar Materi</h4>

                <?php foreach ($materi_array as $materi): ?>
                    <?php
                    $can_access = $materi['can_access'] == 1;
                    $status = $materi['progress_status'];
                    $icon_type = $materi['tipe'];
                    $icon = $icon_type == 'video' ? '🎥' : ($icon_type == 'quiz' ? '📝' : '📄');
                    ?>

                    <div class="materi-item <?= !$can_access ? 'locked' : '' ?>">
                        <div class="materi-icon <?= $icon_type ?>">
                            <?= $icon ?>
                        </div>

                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-0"><?= htmlspecialchars($materi['judul']) ?></h6>
                                <?php if ($status == 'completed'): ?>
                                    <span class="status-badge status-completed">✓ Selesai</span>
                                <?php elseif ($status == 'in_progress'): ?>
                                    <span class="status-badge status-progress">⏳ Sedang Belajar</span>
                                <?php else: ?>
                                    <span class="status-badge status-locked">🔒 Terkunci</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                <?= ucfirst($materi['tipe']) ?> • <?= $materi['durasi_menit'] ?> menit
                            </small>
                        </div>

                        <?php if ($can_access): ?>
                            <a href="view-materi.php?id=<?= $materi['id'] ?>" class="btn btn-primary">
                                <?= $status == 'completed' ? 'Review' : 'Mulai' ?>
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                🔒 Locked
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($materi_array)): ?>
                    <div class="alert alert-info">
                        Belum ada materi di kelas ini.
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <!-- Progress Card -->
                <div class="progress-card">
                    <h5 class="mb-3">Progress Anda</h5>

                    <div class="text-center mb-4">
                        <div style="font-size: 48px; font-weight: 700; color: #667eea;">
                            <?= $progress_percentage ?>%
                        </div>
                        <p class="text-muted mb-0">Complete</p>
                    </div>

                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-primary" style="width: <?= $progress_percentage ?>%"></div>
                    </div>

                    <div class="d-flex justify-content-between text-muted small">
                        <span><?= $completed_count ?> / <?= $total_materi ?> Materi</span>
                        <span><?= $completed_count ?> Selesai</span>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="progress-card">
                    <h6 class="mb-3">💡 Tips Belajar</h6>
                    <ul class="small text-muted mb-0">
                        <li class="mb-2">Selesaikan materi secara berurutan</li>
                        <li class="mb-2">Kerjakan quiz untuk mengetes pemahaman</li>
                        <li class="mb-2">Ulangi materi jika perlu</li>
                        <li>Konsisten belajar setiap hari</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>