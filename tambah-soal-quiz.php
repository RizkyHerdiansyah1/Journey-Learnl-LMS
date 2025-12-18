<?php
require_once 'config.php';
requireRole('dosen');

$user_id = $_SESSION['user_id'];
$materi_id = isset($_GET['materi_id']) ? (int)$_GET['materi_id'] : 0;

// Get materi & cek kepemilikan
$query = "SELECT m.*, k.nama_kelas, k.dosen_id, k.id as kelas_id
          FROM materi m
          JOIN kelas k ON m.kelas_id = k.id
          WHERE m.id = ? AND m.tipe = 'quiz'";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $materi_id);
$stmt->execute();
$result = $stmt->get_result();
$materi = $result->fetch_assoc();

if (!$materi || $materi['dosen_id'] != $user_id) {
    setAlert('danger', 'Materi quiz tidak ditemukan!');
    header('Location: dashboard.php');
    exit;
}

// Proses tambah soal
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pertanyaan = cleanInput($_POST['pertanyaan']);
    $pilihan_a = cleanInput($_POST['pilihan_a']);
    $pilihan_b = cleanInput($_POST['pilihan_b']);
    $pilihan_c = cleanInput($_POST['pilihan_c']);
    $pilihan_d = cleanInput($_POST['pilihan_d']);
    $jawaban_benar = cleanInput($_POST['jawaban_benar']);
    $poin = (int)$_POST['poin'];
    
    if (empty($pertanyaan) || empty($pilihan_a) || empty($pilihan_b) || 
        empty($pilihan_c) || empty($pilihan_d)) {
        setAlert('danger', 'Semua field harus diisi!');
    } else {
        $query = "INSERT INTO kuis (materi_id, pertanyaan, pilihan_a, pilihan_b, pilihan_c, pilihan_d, jawaban_benar, poin) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("issssssi", $materi_id, $pertanyaan, $pilihan_a, $pilihan_b, $pilihan_c, $pilihan_d, $jawaban_benar, $poin);
        
        if ($stmt->execute()) {
            setAlert('success', 'Soal berhasil ditambahkan!');
            
            // Redirect based on action
            if (isset($_POST['save_and_add'])) {
                header('Location: tambah-soal-quiz.php?materi_id=' . $materi_id);
            } else {
                header('Location: kelola-materi.php?kelas_id=' . $materi['kelas_id']);
            }
            exit;
        } else {
            setAlert('danger', 'Gagal menambahkan soal!');
        }
    }
}

// Get existing soal
$query = "SELECT * FROM kuis WHERE materi_id = ? ORDER BY id ASC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $materi_id);
$stmt->execute();
$soal_list = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Soal Quiz - Journey Learn</title>
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
            margin-bottom: 20px;
        }
        .soal-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        .correct-answer {
            background: #d4edda;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
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
        
        <a href="kelola-materi.php?kelas_id=<?= $materi['kelas_id'] ?>" class="btn btn-outline-primary mb-3">
            ← Kembali ke Kelola Materi
        </a>
        
        <!-- Form Tambah Soal -->
        <div class="form-card">
            <h3 class="mb-4">➕ Tambah Soal Quiz</h3>
            <p class="text-muted mb-4">
                Materi: <strong><?= htmlspecialchars($materi['judul']) ?></strong> | 
                Kelas: <strong><?= htmlspecialchars($materi['nama_kelas']) ?></strong>
            </p>
            
            <form method="POST" action="">
                <div class="mb-4">
                    <label class="form-label">Pertanyaan <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="pertanyaan" rows="3" 
                              placeholder="Tuliskan pertanyaan di sini..." required></textarea>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pilihan A <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="pilihan_a" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pilihan B <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="pilihan_b" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pilihan C <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="pilihan_c" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Pilihan D <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="pilihan_d" required>
                    </div>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Jawaban Benar <span class="text-danger">*</span></label>
                        <select class="form-select" name="jawaban_benar" required>
                            <option value="">Pilih jawaban benar</option>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Poin <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="poin" value="10" min="1" required>
                    </div>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" name="save_and_add" class="btn btn-primary flex-grow-1">
                        💾 Simpan & Tambah Lagi
                    </button>
                    <button type="submit" name="save_and_finish" class="btn btn-success flex-grow-1">
                        ✅ Simpan & Selesai
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Daftar Soal yang Sudah Ada -->
        <?php if ($soal_list->num_rows > 0): ?>
        <div class="form-card">
            <h4 class="mb-4">📋 Soal yang Sudah Ditambahkan (<?= $soal_list->num_rows ?>)</h4>
            
            <?php $no = 1; ?>
            <?php while ($soal = $soal_list->fetch_assoc()): ?>
                <div class="soal-card">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h6 class="mb-0"><?= $no ?>. <?= htmlspecialchars($soal['pertanyaan']) ?></h6>
                        <div>
                            <span class="badge bg-warning text-dark"><?= $soal['poin'] ?> poin</span>
                            <a href="hapus-soal-quiz.php?id=<?= $soal['id'] ?>&materi_id=<?= $materi_id ?>" 
                               class="btn btn-sm btn-danger ms-2"
                               onclick="return confirm('Yakin ingin menghapus soal ini?')">
                                🗑️
                            </a>
                        </div>
                    </div>
                    
                    <div class="mt-2">
                        <div class="mb-1">A. <?= htmlspecialchars($soal['pilihan_a']) ?> 
                            <?= $soal['jawaban_benar'] == 'A' ? '<span class="correct-answer">✓ Benar</span>' : '' ?>
                        </div>
                        <div class="mb-1">B. <?= htmlspecialchars($soal['pilihan_b']) ?> 
                            <?= $soal['jawaban_benar'] == 'B' ? '<span class="correct-answer">✓ Benar</span>' : '' ?>
                        </div>
                        <div class="mb-1">C. <?= htmlspecialchars($soal['pilihan_c']) ?> 
                            <?= $soal['jawaban_benar'] == 'C' ? '<span class="correct-answer">✓ Benar</span>' : '' ?>
                        </div>
                        <div>D. <?= htmlspecialchars($soal['pilihan_d']) ?> 
                            <?= $soal['jawaban_benar'] == 'D' ? '<span class="correct-answer">✓ Benar</span>' : '' ?>
                        </div>
                    </div>
                </div>
                <?php $no++; ?>
            <?php endwhile; ?>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>