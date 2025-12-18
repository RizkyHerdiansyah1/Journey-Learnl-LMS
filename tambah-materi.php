<?php
require_once 'config.php';
requireRole('dosen');

$user_id = $_SESSION['user_id'];
$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;

// Cek kepemilikan kelas
$query = "SELECT * FROM kelas WHERE id = ? AND dosen_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $kelas_id, $user_id);
$stmt->execute();
$kelas = $stmt->get_result()->fetch_assoc();

if (!$kelas) {
    setAlert('danger', 'Kelas tidak ditemukan!');
    header('Location: dashboard.php');
    exit;
}

// Proses submit form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $judul = cleanInput($_POST['judul']);
    $tipe = cleanInput($_POST['tipe']);
    $durasi_menit = (int)$_POST['durasi_menit'];
    
    // Ambil konten sesuai tipe
    if ($tipe == 'video') {
        $konten = isset($_POST['video_url']) ? trim($_POST['video_url']) : '';
    } elseif ($tipe == 'text') {
        $konten = isset($_POST['text_content']) ? $_POST['text_content'] : '';
    } else {
        $konten = ''; // Quiz tidak perlu konten di sini
    }
    
    // Get urutan terakhir
    $query = "SELECT MAX(urutan) as max_urutan FROM materi WHERE kelas_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $kelas_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $urutan = ($result['max_urutan'] ?? 0) + 1;
    
    // Validasi
    if (empty($judul)) {
        setAlert('danger', 'Judul materi harus diisi!');
    } elseif ($tipe == 'video' && empty($konten)) {
        setAlert('danger', 'URL YouTube harus diisi!');
    } elseif ($tipe == 'text' && empty($konten)) {
        setAlert('danger', 'Konten text harus diisi!');
    } else {
        // Insert materi
        $query = "INSERT INTO materi (kelas_id, judul, tipe, konten, urutan, durasi_menit) 
                  VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssii", $kelas_id, $judul, $tipe, $konten, $urutan, $durasi_menit);
        
        if ($stmt->execute()) {
            $materi_id = $conn->insert_id;
            
            // Jika quiz, redirect ke halaman tambah soal
            if ($tipe == 'quiz') {
                setAlert('success', 'Materi quiz berhasil dibuat! Sekarang tambahkan soal.');
                header('Location: tambah-soal-quiz.php?materi_id=' . $materi_id);
                exit;
            } else {
                setAlert('success', 'Materi berhasil ditambahkan!');
                header('Location: kelola-materi.php?kelas_id=' . $kelas_id);
                exit;
            }
        } else {
            setAlert('danger', 'Gagal menambahkan materi!');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Materi - Journey Learn</title>
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
        }
        .tipe-option {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .tipe-option:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }
        .tipe-option input[type="radio"] {
            display: none;
        }
        .tipe-option input[type="radio"]:checked + label {
            color: #667eea;
        }
        .tipe-option.active {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.1);
        }
        .content-field {
            display: none;
        }
        .content-field.active {
            display: block;
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
        
        <a href="kelola-materi.php?kelas_id=<?= $kelas_id ?>" class="btn btn-outline-primary mb-3">
            ← Kembali ke Kelola Materi
        </a>
        
        <div class="form-card">
            <h3 class="mb-4">➕ Tambah Materi Baru</h3>
            <p class="text-muted mb-4">Kelas: <strong><?= htmlspecialchars($kelas['nama_kelas']) ?></strong></p>
            
            <form method="POST" action="" id="materiForm">
                <!-- Judul -->
                <div class="mb-4">
                    <label class="form-label">Judul Materi <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="judul" 
                           placeholder="Contoh: Pengenalan HTML" required>
                </div>
                
                <!-- Tipe Materi -->
                <div class="mb-4">
                    <label class="form-label">Tipe Materi <span class="text-danger">*</span></label>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="tipe-option" onclick="selectTipe('video')">
                                <input type="radio" name="tipe" value="video" id="tipe_video" required>
                                <label for="tipe_video" style="cursor: pointer; width: 100%;">
                                    <div style="font-size: 48px; margin-bottom: 10px;">🎥</div>
                                    <h6>Video</h6>
                                    <small class="text-muted">YouTube Embed</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="tipe-option" onclick="selectTipe('quiz')">
                                <input type="radio" name="tipe" value="quiz" id="tipe_quiz">
                                <label for="tipe_quiz" style="cursor: pointer; width: 100%;">
                                    <div style="font-size: 48px; margin-bottom: 10px;">📝</div>
                                    <h6>Quiz</h6>
                                    <small class="text-muted">Multiple Choice</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="tipe-option" onclick="selectTipe('text')">
                                <input type="radio" name="tipe" value="text" id="tipe_text">
                                <label for="tipe_text" style="cursor: pointer; width: 100%;">
                                    <div style="font-size: 48px; margin-bottom: 10px;">📄</div>
                                    <h6>Text</h6>
                                    <small class="text-muted">Rich Text</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Konten Video -->
                <div class="mb-4 content-field" id="content_video">
                    <label class="form-label">URL YouTube Embed <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="video_url" id="video_url"
                           placeholder="https://www.youtube.com/embed/VIDEO_ID">
                    <small class="text-muted">
                        📌 Tips: Buka video di YouTube → Klik Share → Embed → Copy URL-nya<br>
                        Contoh: https://www.youtube.com/embed/qz0aGYrrlhU
                    </small>
                </div>
                
                <!-- Konten Quiz -->
                <div class="mb-4 content-field" id="content_quiz">
                    <div class="alert alert-info">
                        <strong>📝 Info:</strong> Setelah membuat materi quiz, Anda akan diarahkan untuk menambahkan soal-soal.
                    </div>
                </div>
                
                <!-- Konten Text -->
                <div class="mb-4 content-field" id="content_text">
                    <label class="form-label">Konten Materi <span class="text-danger">*</span></label>
                    <textarea class="form-control" name="text_content" id="text_content" rows="10"
                              placeholder="Tulis konten materi di sini... Anda bisa gunakan HTML."></textarea>
                    <small class="text-muted">Anda bisa menggunakan HTML untuk formatting (h2, p, strong, em, ul, li, dll)</small>
                </div>
                
                <!-- Durasi -->
                <div class="mb-4">
                    <label class="form-label">Estimasi Durasi (menit) <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" name="durasi_menit" 
                           placeholder="30" min="1" value="30" required>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                        💾 Simpan Materi
                    </button>
                    <a href="kelola-materi.php?kelas_id=<?= $kelas_id ?>" class="btn btn-outline-secondary btn-lg">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectTipe(tipe) {
            // Remove active class from all
            document.querySelectorAll('.tipe-option').forEach(el => {
                el.classList.remove('active');
            });
            
            // Add active class to selected
            document.querySelector(`#tipe_${tipe}`).checked = true;
            document.querySelector(`#tipe_${tipe}`).parentElement.classList.add('active');
            
            // Hide all content fields
            document.querySelectorAll('.content-field').forEach(el => {
                el.classList.remove('active');
            });
            
            // Show selected content field
            document.getElementById(`content_${tipe}`).classList.add('active');
            
            // Handle required attributes
            document.getElementById('video_url').required = (tipe === 'video');
            document.getElementById('text_content').required = (tipe === 'text');
        }
        
        // Set default to video
        selectTipe('video');
    </script>
</body>
</html>