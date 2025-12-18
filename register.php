<?php
require_once 'config.php';

// Jika sudah login, redirect
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Proses register
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = cleanInput($_POST['nama']);
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = cleanInput($_POST['role']);
    
    if (empty($nama) || empty($email) || empty($password)) {
        setAlert('danger', 'Semua field harus diisi!');
    } elseif ($password != $confirm_password) {
        setAlert('danger', 'Password dan konfirmasi password tidak sama!');
    } elseif (strlen($password) < 6) {
        setAlert('danger', 'Password minimal 6 karakter!');
    } else {
        // Cek email sudah terdaftar atau belum
        $query = "SELECT id FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            setAlert('danger', 'Email sudah terdaftar!');
        } else {
            // Insert user baru
            $hashed_password = hashPassword($password);
            $query = "INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssss", $nama, $email, $hashed_password, $role);
            
            if ($stmt->execute()) {
                setAlert('success', 'Registrasi berhasil! Silakan login.');
                header('Location: login.php');
                exit;
            } else {
                setAlert('danger', 'Registrasi gagal! Coba lagi.');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Journey Learn</title>
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
        .register-card {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }
        .register-body {
            padding: 40px 30px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.4);
        }
        .logo-icon {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
        }
    </style>
</head>
<body>
    <div class="register-card">
        <div class="register-header">
            <div class="logo-icon">🎓</div>
            <h2 class="mb-2">Journey Learn</h2>
            <p class="mb-0">Buat akun baru</p>
        </div>
        
        <div class="register-body">
            <?php showAlert(); ?>
            
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control" id="nama" name="nama" 
                           placeholder="Nama lengkap Anda" required>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="nama@email.com" required>
                </div>
                
                <div class="mb-3">
                    <label for="role" class="form-label">Daftar Sebagai</label>
                    <select class="form-select" id="role" name="role" required>
                        <option value="">Pilih role</option>
                        <option value="mahasiswa">Mahasiswa</option>
                        <option value="dosen">Dosen</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Minimal 6 karakter" required>
                </div>
                
                <div class="mb-4">
                    <label for="confirm_password" class="form-label">Konfirmasi Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Ketik ulang password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-register w-100 mb-3">
                    Daftar Sekarang
                </button>
                
                <div class="text-center">
                    <small class="text-muted">
                        Sudah punya akun? 
                        <a href="login.php" class="text-decoration-none">Login di sini</a>
                    </small>
                </div>
            </form>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>