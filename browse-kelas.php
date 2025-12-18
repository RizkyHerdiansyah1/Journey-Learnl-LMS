<?php
require_once 'config.php';
requireRole('mahasiswa'); // Hanya mahasiswa yang bisa akses

$user_id = $_SESSION['user_id'];

// Proses enroll kelas
if (isset($_POST['enroll'])) {
    $kelas_id = (int)$_POST['kelas_id'];
    
    // Cek apakah sudah enroll
    $query = "SELECT id FROM enrollment WHERE user_id = ? AND kelas_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $kelas_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        setAlert('warning', 'Anda sudah enroll kelas ini!');
    } else {
        // Insert enrollment
        $query = "INSERT INTO enrollment (user_id, kelas_id) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $user_id, $kelas_id);
        
        if ($stmt->execute()) {
            // Unlock materi pertama
            $query = "SELECT id FROM materi WHERE kelas_id = ? ORDER BY urutan ASC LIMIT 1";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $kelas_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $materi = $result->fetch_assoc();
                updateProgress($user_id, $materi['id'], 'in_progress');
            }
            
            setAlert('success', 'Berhasil enroll kelas! Mulai belajar sekarang.');
            header('Location: dashboard.php');
            exit;
        } else {
            setAlert('danger', 'Gagal enroll kelas. Coba lagi.');
        }
    }
}

// Get semua kelas yang tersedia
$query = "SELECT k.*, u.nama as nama_dosen,
          (SELECT COUNT(*) FROM enrollment WHERE kelas_id = k.id) as total_mahasiswa,
          (SELECT COUNT(*) FROM materi WHERE kelas_id = k.id) as total_materi,
          (SELECT COUNT(*) FROM enrollment WHERE kelas_id = k.id AND user_id = ?) as is_enrolled
          FROM kelas k
          JOIN users u ON k.dosen_id = u.id
          ORDER BY k.tanggal_dibuat DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$all_kelas = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jelajah Kelas - Journey Learn</title>
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
        .class-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: transform 0.3s;
            height: 100%;
        }
        .class-card:hover {
            transform: translateY(-5px);
        }
        .class-header {
            height: 180px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
        }
        .class-header.alt1 { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); }
        .class-header.alt2 { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .class-header.alt3 { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .badge-enrolled {
            position: absolute;
            top: 15px;
            right: 15px;
            background: white;
            color: #667eea;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
                    <li class="nav-item"><a class="nav-link active" href="browse-kelas.php">Jelajah Kelas</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php showAlert(); ?>
        
        <div class="mb-4">
            <h2>Jelajahi Kelas</h2>
            <p class="text-muted">Temukan kelas yang sesuai dengan minat Anda</p>
        </div>

        <div class="row">
            <?php if ($all_kelas->num_rows > 0): ?>
                <?php $color_variants = ['', 'alt1', 'alt2', 'alt3']; ?>
                <?php $i = 0; ?>
                <?php while ($kelas = $all_kelas->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="class-card">
                        <div class="class-header <?= $color_variants[$i % 4] ?>">
                            <?php if ($kelas['is_enrolled'] > 0): ?>
                                <span class="badge-enrolled">✓ Enrolled</span>
                            <?php endif; ?>
                        </div>
                        <div class="p-4">
                            <span class="badge bg-primary mb-2"><?= htmlspecialchars($kelas['kategori']) ?></span>
                            <h5 class="mb-2"><?= htmlspecialchars($kelas['nama_kelas']) ?></h5>
                            <p class="text-muted small mb-3">
                                <?= htmlspecialchars(substr($kelas['deskripsi'], 0, 100)) ?>...
                            </p>
                            
                            <div class="d-flex gap-3 mb-3 text-muted small">
                                <span>👨‍🏫 <?= htmlspecialchars($kelas['nama_dosen']) ?></span>
                            </div>
                            
                            <div class="d-flex gap-3 mb-3 text-muted small">
                                <span>👥 <?= $kelas['total_mahasiswa'] ?> siswa</span>
                                <span>📚 <?= $kelas['total_materi'] ?> materi</span>
                            </div>
                            
                            <?php if ($kelas['is_enrolled'] > 0): ?>
                                <a href="detail-kelas.php?id=<?= $kelas['id'] ?>" class="btn btn-success w-100">
                                    Lanjut Belajar
                                </a>
                            <?php else: ?>
                                <form method="POST" action="">
                                    <input type="hidden" name="kelas_id" value="<?= $kelas['id'] ?>">
                                    <button type="submit" name="enroll" class="btn btn-primary w-100">
                                        Enroll Sekarang
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php $i++; ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-info">Belum ada kelas tersedia.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>