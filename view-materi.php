<?php
require_once 'config.php';
requireRole('mahasiswa');

$user_id = $_SESSION['user_id'];
$materi_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Get detail materi
$query = "SELECT m.*, k.nama_kelas, k.id as kelas_id
          FROM materi m
          JOIN kelas k ON m.kelas_id = k.id
          WHERE m.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $materi_id);
$stmt->execute();
$result = $stmt->get_result();
$materi = $result->fetch_assoc();

if (!$materi) {
    setAlert('danger', 'Materi tidak ditemukan!');
    header('Location: dashboard.php');
    exit;
}

// Cek apakah user sudah enroll kelas ini
$query = "SELECT * FROM enrollment WHERE user_id = ? AND kelas_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $materi['kelas_id']);
$stmt->execute();
if ($stmt->get_result()->num_rows == 0) {
    setAlert('danger', 'Anda belum enroll kelas ini!');
    header('Location: browse-kelas.php');
    exit;
}

// Cek apakah bisa akses materi ini (sequential learning)
if (!canAccessMateri($user_id, $materi_id)) {
    setAlert('warning', 'Selesaikan materi sebelumnya terlebih dahulu!');
    header('Location: detail-kelas.php?id=' . $materi['kelas_id']);
    exit;
}

// Update progress ke in_progress jika belum
$query = "SELECT status FROM progress WHERE user_id = ? AND materi_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $materi_id);
$stmt->execute();
$result = $stmt->get_result();
$current_progress = $result->fetch_assoc();

if (!$current_progress || $current_progress['status'] == 'locked') {
    updateProgress($user_id, $materi_id, 'in_progress');
}

// Proses submit quiz
$quiz_result = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_quiz'])) {
    // 🔥 FIX: Cek attempt count untuk batasi quiz retries
    $query = "SELECT COUNT(*) as attempt_count FROM hasil_kuis 
              WHERE user_id = ? AND materi_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $materi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $attempts = $result->fetch_assoc();

    // Batasi maksimal 3x attempt
    if ($attempts['attempt_count'] >= 3) {
        setAlert('warning', '⚠️ Anda sudah mencapai batas maksimal 3x percobaan untuk quiz ini!');
        header('Location: detail-kelas.php?id=' . $materi['kelas_id']);
        exit;
    }

    // Get semua soal quiz
    $query = "SELECT * FROM kuis WHERE materi_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $materi_id);
    $stmt->execute();
    $soal_list = $stmt->get_result();

    $skor = 0;
    $total_soal = 0;
    $jawaban_detail = [];

    while ($soal = $soal_list->fetch_assoc()) {
        $total_soal++;
        $jawaban_user = isset($_POST['jawaban_' . $soal['id']]) ? $_POST['jawaban_' . $soal['id']] : '';
        $is_benar = ($jawaban_user == $soal['jawaban_benar']);

        if ($is_benar) {
            $skor += $soal['poin'];
        }

        $jawaban_detail[] = [
            'pertanyaan' => $soal['pertanyaan'],
            'jawaban_user' => $jawaban_user,
            'jawaban_benar' => $soal['jawaban_benar'],
            'is_benar' => $is_benar,
            'poin' => $is_benar ? $soal['poin'] : 0
        ];
    }

    // Simpan hasil quiz
    $jawaban_json = json_encode($jawaban_detail);
    $query = "INSERT INTO hasil_kuis (user_id, materi_id, skor, total_soal, jawaban_detail) 
              VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiiis", $user_id, $materi_id, $skor, $total_soal, $jawaban_json);
    $stmt->execute();

    // Update progress jadi completed
    updateProgress($user_id, $materi_id, 'completed');

    // Unlock materi berikutnya
    $query = "SELECT id FROM materi WHERE kelas_id = ? AND urutan = ?";
    $stmt = $conn->prepare($query);
    $next_urutan = $materi['urutan'] + 1;
    $stmt->bind_param("ii", $materi['kelas_id'], $next_urutan);
    $stmt->execute();
    $next_materi = $stmt->get_result()->fetch_assoc();

    if ($next_materi) {
        updateProgress($user_id, $next_materi['id'], 'in_progress');
    }

    $quiz_result = [
        'skor' => $skor,
        'total_soal' => $total_soal,
        'jawaban_detail' => $jawaban_detail
    ];
}

// Proses mark as complete (untuk video & text)
if (isset($_POST['mark_complete'])) {
    updateProgress($user_id, $materi_id, 'completed');

    // Unlock materi berikutnya
    $query = "SELECT id FROM materi WHERE kelas_id = ? AND urutan = ?";
    $stmt = $conn->prepare($query);
    $next_urutan = $materi['urutan'] + 1;
    $stmt->bind_param("ii", $materi['kelas_id'], $next_urutan);
    $stmt->execute();
    $next_materi = $stmt->get_result()->fetch_assoc();

    if ($next_materi) {
        updateProgress($user_id, $next_materi['id'], 'in_progress');
    }

    setAlert('success', 'Materi berhasil diselesaikan!');
    header('Location: detail-kelas.php?id=' . $materi['kelas_id']);
    exit;
}

// Get quiz questions jika tipe quiz
$quiz_questions = [];
$current_attempts = 0;
if ($materi['tipe'] == 'quiz') {
    $query = "SELECT * FROM kuis WHERE materi_id = ? ORDER BY id ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $materi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($q = $result->fetch_assoc()) {
        $quiz_questions[] = $q;
    }

    // Get current attempt count
    $query = "SELECT COUNT(*) as attempt_count FROM hasil_kuis 
              WHERE user_id = ? AND materi_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $user_id, $materi_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $attempt_data = $result->fetch_assoc();
    $current_attempts = $attempt_data['attempt_count'];
}

// Get materi sebelumnya dan berikutnya
$query = "SELECT id, judul FROM materi WHERE kelas_id = ? AND urutan < ? ORDER BY urutan DESC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $materi['kelas_id'], $materi['urutan']);
$stmt->execute();
$prev_materi = $stmt->get_result()->fetch_assoc();

$query = "SELECT id, judul FROM materi WHERE kelas_id = ? AND urutan > ? ORDER BY urutan ASC LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $materi['kelas_id'], $materi['urutan']);
$stmt->execute();
$next_materi = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($materi['judul']) ?> - Journey Learn</title>
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

        .content-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 20px;
        }

        .video-container {
            position: relative;
            padding-bottom: 56.25%;
            height: 0;
            overflow: hidden;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .video-container iframe {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .quiz-option {
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .quiz-option:hover {
            border-color: #667eea;
            background: rgba(102, 126, 234, 0.05);
        }

        .quiz-option input[type="radio"] {
            margin-right: 10px;
        }

        .result-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin-bottom: 20px;
        }

        .answer-review {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #dee2e6;
        }

        .answer-review.correct {
            border-left-color: #28a745;
            background: rgba(40, 167, 69, 0.05);
        }

        .answer-review.wrong {
            border-left-color: #dc3545;
            background: rgba(220, 53, 69, 0.05);
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

        <a href="detail-kelas.php?id=<?= $materi['kelas_id'] ?>" class="btn btn-outline-primary mb-3">
            ← Kembali ke Kelas
        </a>

        <div class="content-card">
            <div class="mb-3">
                <span class="badge bg-primary"><?= ucfirst($materi['tipe']) ?></span>
                <span class="badge bg-secondary"><?= $materi['durasi_menit'] ?> menit</span>
            </div>

            <h2 class="mb-4"><?= htmlspecialchars($materi['judul']) ?></h2>

            <!-- VIDEO CONTENT -->
            <?php if ($materi['tipe'] == 'video' && !$quiz_result): ?>
                <?php
                // Validasi URL YouTube
                $video_url = $materi['konten'];
                $is_valid_youtube = (strpos($video_url, 'youtube.com/embed/') !== false ||
                    strpos($video_url, 'youtu.be/') !== false);
                ?>

                <?php if ($is_valid_youtube): ?>
                    <div class="video-container">
                        <iframe src="<?= htmlspecialchars($video_url) ?>" frameborder="0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen loading="lazy">
                        </iframe>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <strong>⚠️ Video tidak dapat dimuat</strong><br>
                        URL: <code><?= htmlspecialchars($video_url) ?></code><br><br>
                        Pastikan format URL adalah: <code>https://www.youtube.com/embed/VIDEO_ID</code>
                    </div>
                <?php endif; ?>

                <div class="mt-3">
                    <p class="text-muted mb-3">
                        <strong>📺 Video URL:</strong>
                        <a href="<?= htmlspecialchars($video_url) ?>" target="_blank" class="text-decoration-none">
                            Buka di tab baru
                        </a>
                    </p>
                </div>

                <form method="POST" action="">
                    <button type="submit" name="mark_complete" class="btn btn-success btn-lg w-100">
                        ✓ Tandai Selesai & Lanjut ke Materi Berikutnya
                    </button>
                </form>
            <?php endif; ?>

            <!-- TEXT CONTENT -->
            <?php if ($materi['tipe'] == 'text' && !$quiz_result): ?>
                <div class="content-text mb-4">
                    <?= htmlspecialchars($materi['konten'], ENT_QUOTES, 'UTF-8') ?>
                </div>

                <form method="POST" action="">
                    <button type="submit" name="mark_complete" class="btn btn-success btn-lg w-100">
                        ✓ Tandai Selesai & Lanjut ke Materi Berikutnya
                    </button>
                </form>
            <?php endif; ?>

            <!-- QUIZ CONTENT -->
            <?php if ($materi['tipe'] == 'quiz' && !$quiz_result): ?>
                <div class="alert alert-info mb-4">
                    <strong>📝 Petunjuk:</strong> Pilih jawaban yang paling tepat untuk setiap pertanyaan.
                    Setelah selesai, klik tombol Submit untuk melihat hasil.
                    <hr class="my-2">
                    <small>
                        🔄 <strong>Percobaan:</strong> <?= $current_attempts ?> dari 3 (Sisa: <?= 3 - $current_attempts ?> kali)
                    </small>
                </div>

                <form method="POST" action="">
                    <?php foreach ($quiz_questions as $index => $soal): ?>
                        <div class="mb-4">
                            <h5 class="mb-3">
                                <?= ($index + 1) ?>. <?= htmlspecialchars($soal['pertanyaan']) ?>
                                <span class="badge bg-warning text-dark"><?= $soal['poin'] ?> poin</span>
                            </h5>

                            <label class="quiz-option">
                                <input type="radio" name="jawaban_<?= $soal['id'] ?>" value="A" required>
                                A. <?= htmlspecialchars($soal['pilihan_a']) ?>
                            </label>

                            <label class="quiz-option">
                                <input type="radio" name="jawaban_<?= $soal['id'] ?>" value="B">
                                B. <?= htmlspecialchars($soal['pilihan_b']) ?>
                            </label>

                            <label class="quiz-option">
                                <input type="radio" name="jawaban_<?= $soal['id'] ?>" value="C">
                                C. <?= htmlspecialchars($soal['pilihan_c']) ?>
                            </label>

                            <label class="quiz-option">
                                <input type="radio" name="jawaban_<?= $soal['id'] ?>" value="D">
                                D. <?= htmlspecialchars($soal['pilihan_d']) ?>
                            </label>
                        </div>
                    <?php endforeach; ?>

                    <button type="submit" name="submit_quiz" class="btn btn-primary btn-lg w-100">
                        📤 Submit Quiz
                    </button>
                </form>
            <?php endif; ?>

            <!-- QUIZ RESULT -->
            <?php if ($quiz_result): ?>
                <div class="result-card">
                    <h3 class="mb-3">🎉 Quiz Selesai!</h3>
                    <div style="font-size: 48px; font-weight: 700; margin: 20px 0;">
                        <?= $quiz_result['skor'] ?> / <?= $quiz_result['total_soal'] * 10 ?>
                    </div>
                    <p class="mb-0">
                        Anda benar
                        <?= array_reduce($quiz_result['jawaban_detail'], function ($c, $j) {
                            return $c + ($j['is_benar'] ? 1 : 0);
                        }, 0) ?>
                        dari <?= $quiz_result['total_soal'] ?> soal
                    </p>
                </div>

                <h4 class="mb-3">📊 Review Jawaban:</h4>

                <?php foreach ($quiz_result['jawaban_detail'] as $index => $jawaban): ?>
                    <div class="answer-review <?= $jawaban['is_benar'] ? 'correct' : 'wrong' ?>">
                        <h6 class="mb-2">
                            <?= ($index + 1) ?>. <?= htmlspecialchars($jawaban['pertanyaan']) ?>
                        </h6>
                        <div class="mb-1">
                            <strong>Jawaban Anda:</strong>
                            <span class="badge <?= $jawaban['is_benar'] ? 'bg-success' : 'bg-danger' ?>">
                                <?= $jawaban['jawaban_user'] ?>
                            </span>
                        </div>
                        <?php if (!$jawaban['is_benar']): ?>
                            <div>
                                <strong>Jawaban Benar:</strong>
                                <span class="badge bg-success"><?= $jawaban['jawaban_benar'] ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <a href="detail-kelas.php?id=<?= $materi['kelas_id'] ?>" class="btn btn-primary btn-lg w-100 mt-3">
                    ✓ Lanjut ke Materi Berikutnya
                </a>
            <?php endif; ?>
        </div>

        <!-- Navigation -->
        <div class="d-flex justify-content-between">
            <?php if ($prev_materi): ?>
                <a href="view-materi.php?id=<?= $prev_materi['id'] ?>" class="btn btn-outline-secondary">
                    ← <?= htmlspecialchars($prev_materi['judul']) ?>
                </a>
            <?php else: ?>
                <div></div>
            <?php endif; ?>

            <?php if ($next_materi && canAccessMateri($user_id, $next_materi['id'])): ?>
                <a href="view-materi.php?id=<?= $next_materi['id'] ?>" class="btn btn-outline-primary">
                    <?= htmlspecialchars($next_materi['judul']) ?> →
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>