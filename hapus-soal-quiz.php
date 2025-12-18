<?php
require_once 'config.php';
requireRole('dosen');

$soal_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$materi_id = isset($_GET['materi_id']) ? (int)$_GET['materi_id'] : 0;

// Delete soal
$query = "DELETE FROM kuis WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $soal_id);

if ($stmt->execute()) {
    setAlert('success', 'Soal berhasil dihapus!');
} else {
    setAlert('danger', 'Gagal menghapus soal!');
}

header('Location: tambah-soal-quiz.php?materi_id=' . $materi_id);
exit;
?>