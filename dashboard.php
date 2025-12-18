<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$nama = $_SESSION['nama'];

// ================================================
// QUERY DATA UNTUK MAHASISWA
// ================================================
if ($role == 'mahasiswa') {
    // Get statistik dari view
    $query = "SELECT * FROM v_mahasiswa_stats WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $stats = $result->fetch_assoc();

    // Get kelas yang di-enroll
    $query = "SELECT k.*, u.nama as nama_dosen, 
              (SELECT COUNT(*) FROM materi WHERE kelas_id = k.id) as total_materi,
              (SELECT COUNT(*) FROM progress p JOIN materi m ON p.materi_id = m.id 
               WHERE p.user_id = ? AND m.kelas_id = k.id AND p.status = 'completed') as materi_selesai
              FROM kelas k
              JOIN users u ON k.dosen_id = u.id
              JOIN enrollment e ON k.id = e.kelas_id
              WHERE e.user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $kelas_enrolled = $stmt->get_result();
}

// ================================================
// QUERY DATA UNTUK DOSEN
// ================================================
if ($role == 'dosen') {
    // Get total kelas dosen
    $query = "SELECT COUNT(*) as total FROM kelas WHERE dosen_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_kelas = $result->fetch_assoc()['total'];

    // Get total mahasiswa
    $query = "SELECT COUNT(DISTINCT e.user_id) as total 
              FROM enrollment e 
              JOIN kelas k ON e.kelas_id = k.id 
              WHERE k.dosen_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $total_mahasiswa = $result->fetch_assoc()['total'];

    // Get kelas yang dibuat dosen
    $query = "SELECT k.*, 
              (SELECT COUNT(*) FROM enrollment WHERE kelas_id = k.id) as total_mahasiswa,
              (SELECT COUNT(*) FROM materi WHERE kelas_id = k.id) as total_materi
              FROM kelas k
              WHERE k.dosen_id = ?
              ORDER BY k.tanggal_dibuat DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $kelas_dosen = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Journey Learn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .navbar {
            background: white !important;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary) !important;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin-bottom: 15px;
        }

        .class-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s;
            height: 100%;
        }

        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        }

        .class-card-header {
            height: 150px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            position: relative;
            overflow: hidden;
        }

        .class-card-header.green {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        }

        .progress-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: conic-gradient(var(--primary) 0deg, var(--primary) calc(3.6deg * var(--progress)), #e9ecef calc(3.6deg * var(--progress)));
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .progress-circle::before {
            content: '';
            width: 45px;
            height: 45px;
            background: white;
            border-radius: 50%;
            position: absolute;
        }

        .progress-text {
            position: relative;
            z-index: 1;
            font-weight: 700;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                🎓 Journey Learn
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <?php if ($role == 'mahasiswa'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="browse-kelas.php">Jelajah Kelas</a>
                        </li>
                    <?php endif; ?>
                    <?php if ($role == 'dosen'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="kelola-kelas.php">Kelola Kelas</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button"
                            data-bs-toggle="dropdown">
                            <?= htmlspecialchars($nama) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php">Profil</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php showAlert(); ?>

        <!-- Dashboard Mahasiswa -->
        <?php if ($role == 'mahasiswa'): ?>
            <div class="mb-4">
                <h2>Welcome back, <?= htmlspecialchars($nama) ?>!</h2>
                <p class="text-muted">Continue your learning journey</p>
            </div>

            <!-- Statistik Cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(102, 126, 234, 0.1);">📚</div>
                        <h3 class="mb-0"><?= $stats['enrolled_classes'] ?? 0 ?></h3>
                        <p class="text-muted mb-0">Enrolled Classes</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(56, 239, 125, 0.1);">📈</div>
                        <h3 class="mb-0"><?= $stats['average_progress'] ?? 0 ?>%</h3>
                        <p class="text-muted mb-0">Average Progress</p>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(255, 159, 64, 0.1);">⏰</div>
                        <h3 class="mb-0"><?= $stats['hours_learned'] ?? 0 ?></h3>
                        <p class="text-muted mb-0">Hours Learned</p>
                    </div>
                </div>
            </div>

            <!-- My Classes -->
            <h4 class="mb-3">My Classes</h4>
            <div class="row">
                <?php if ($kelas_enrolled->num_rows > 0): ?>
                    <?php while ($kelas = $kelas_enrolled->fetch_assoc()):
                        $progress = $kelas['total_materi'] > 0 ?
                            round(($kelas['materi_selesai'] / $kelas['total_materi']) * 100) : 0;
                        ?>
                        <div class="col-md-6 mb-4">
                            <div class="class-card">
                                <div class="class-card-header <?= $kelas['id'] % 2 == 0 ? 'green' : '' ?>">
                                </div>
                                <div class="p-4">
                                    <h5 class="mb-2"><?= htmlspecialchars($kelas['nama_kelas']) ?></h5>
                                    <p class="text-muted small mb-3"><?= htmlspecialchars($kelas['deskripsi']) ?></p>

                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div>
                                            <small class="text-muted">📖 <?= $kelas['total_materi'] ?> materi</small>
                                        </div>
                                        <div class="progress-circle" style="--progress: <?= $progress ?>">
                                            <span class="progress-text"><?= $progress ?>%</span>
                                        </div>
                                    </div>

                                    <a href="detail-kelas.php?id=<?= $kelas['id'] ?>" class="btn btn-primary w-100">
                                        Continue Learning
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            Anda belum enroll kelas apapun.
                            <a href="browse-kelas.php" class="alert-link">Jelajahi kelas sekarang!</a>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="col-md-6 mb-4">
                    <div class="class-card d-flex align-items-center justify-content-center"
                        style="min-height: 350px; cursor: pointer;" onclick="window.location.href='browse-kelas.php'">
                        <div class="text-center">
                            <div style="font-size: 60px; margin-bottom: 20px;">➕</div>
                            <h5>Enroll in Class</h5>
                            <p class="text-muted">Explore more courses</p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Dashboard Dosen -->
        <?php if ($role == 'dosen'): ?>
            <div class="mb-4">
                <h2>Dashboard Dosen</h2>
                <p class="text-muted">Kelola kelas dan mahasiswa Anda</p>
            </div>

            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(102, 126, 234, 0.1);">📚</div>
                        <h3 class="mb-0"><?= $total_kelas ?></h3>
                        <p class="text-muted mb-0">Total Kelas</p>
                    </div>
                </div>
                <div class="col-md-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: rgba(56, 239, 125, 0.1);">👥</div>
                        <h3 class="mb-0"><?= $total_mahasiswa ?></h3>
                        <p class="text-muted mb-0">Total Mahasiswa</p>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0">Kelas Anda</h4>
                <a href="tambah-kelas.php" class="btn btn-primary">+ Buat Kelas Baru</a>
            </div>

            <div class="row">
                <?php if ($kelas_dosen->num_rows > 0): ?>
                    <?php while ($kelas = $kelas_dosen->fetch_assoc()): ?>
                        <div class="col-md-6 mb-4">
                            <div class="class-card">
                                <div class="class-card-header">
                                </div>
                                <div class="p-4">
                                    <h5 class="mb-2"><?= htmlspecialchars($kelas['nama_kelas']) ?></h5>
                                    <p class="text-muted small mb-3"><?= htmlspecialchars($kelas['deskripsi']) ?></p>

                                    <div class="d-flex gap-3 mb-3">
                                        <small class="text-muted">👥 <?= $kelas['total_mahasiswa'] ?> mahasiswa</small>
                                        <small class="text-muted">📖 <?= $kelas['total_materi'] ?> materi</small>
                                    </div>

                                    <a href="kelola-materi.php?kelas_id=<?= $kelas['id'] ?>" class="btn btn-primary w-100">
                                        Kelola Kelas
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            Anda belum membuat kelas. <a href="tambah-kelas.php" class="alert-link">Buat kelas pertama Anda!</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>