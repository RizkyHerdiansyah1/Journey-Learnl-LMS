<?php
require_once 'config.php';

// File ini HANYA untuk reset password saat development
// HAPUS file ini setelah production!

$success_messages = [];

// Reset password untuk semua user menjadi "password123"
$new_password = 'password123';
$hashed = password_hash($new_password, PASSWORD_DEFAULT);

// Update semua user
$emails = [
    'rizkyherdiansyahr@gmail.com',
    'budi.dosen@gmail.com',
    'siti.dosen@gmail.com',
    'andi.mahasiswa@gmail.com'
];

foreach ($emails as $email) {
    $query = "UPDATE users SET password = ? WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $hashed, $email);
    
    if ($stmt->execute()) {
        $success_messages[] = "✅ Password untuk $email berhasil direset";
    } else {
        $success_messages[] = "❌ Gagal reset password untuk $email";
    }
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Journey Learn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .card {
            max-width: 600px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-body p-5">
            <h3 class="text-center mb-4">🔐 Password Reset Berhasil!</h3>
            
            <div class="alert alert-success">
                <?php foreach ($success_messages as $msg): ?>
                    <div><?= $msg ?></div>
                <?php endforeach; ?>
            </div>
            
            <hr class="my-4">
            
            <h5 class="mb-3">📋 Akun yang Sudah Direset:</h5>
            
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Email</th>
                            <th>Password</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>rizkyherdiansyahr@gmail.com</td>
                            <td><code>password123</code></td>
                            <td><span class="badge bg-info">Mahasiswa</span></td>
                        </tr>
                        <tr>
                            <td>budi.dosen@gmail.com</td>
                            <td><code>password123</code></td>
                            <td><span class="badge bg-primary">Dosen</span></td>
                        </tr>
                        <tr>
                            <td>siti.dosen@gmail.com</td>
                            <td><code>password123</code></td>
                            <td><span class="badge bg-primary">Dosen</span></td>
                        </tr>
                        <tr>
                            <td>andi.mahasiswa@gmail.com</td>
                            <td><code>password123</code></td>
                            <td><span class="badge bg-info">Mahasiswa</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="alert alert-warning mt-4">
                <strong>⚠️ PENTING:</strong> Hapus file <code>reset-password.php</code> ini setelah selesai testing!
            </div>
            
            <div class="text-center mt-4">
                <a href="login.php" class="btn btn-primary btn-lg">
                    🚀 Login Sekarang
                </a>
            </div>
        </div>
    </div>
</body>
</html>