<?php
require_once 'config.php';

// Jika sudah login, redirect ke dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Proses login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = cleanInput($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        setAlert('danger', 'Email dan password harus diisi!');
    } else {
        // Query user berdasarkan email
        $query = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            // Verify password
            if (verifyPassword($password, $user['password'])) {
                // Di login.php setelah login berhasil
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['foto_profil'] = $user['foto_profil'];

                setAlert('success', 'Login berhasil! Selamat datang, ' . $user['nama']);
                header('Location: dashboard.php');
                exit;
            } else {
                setAlert('danger', 'Password salah!');
            }
        } else {
            setAlert('danger', 'Email tidak ditemukan!');
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Journey Learn</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .login-card {
            max-width: 450px;
            width: 100%;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
        }

        .login-body {
            padding: 40px 30px;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            padding: 12px;
            font-weight: 600;
        }

        .btn-login:hover {
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
    <div class="login-card">
        <div class="login-header">
            <div class="logo-icon">🎓</div>
            <h2 class="mb-2">Journey Learn</h2>
            <p class="mb-0">Masuk ke akun Anda</p>
        </div>

        <div class="login-body">
            <?php showAlert(); ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="nama@email.com"
                        required>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password"
                        placeholder="Masukkan password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-login w-100 mb-3">
                    Masuk
                </button>

                <div class="text-center">
                    <small class="text-muted">
                        Belum punya akun?
                        <a href="register.php" class="text-decoration-none">Daftar sekarang</a>
                    </small>
                </div>
            </form>

            <hr class="my-4">

            <div class="text-center">
                <small class="text-muted d-block mb-2">📌 <strong>Akun Demo:</strong></small>
                <small class="text-muted d-block">
                    <strong>Dosen:</strong> budi.dosen@gmail.com<br>
                    <strong>Mahasiswa:</strong> rizkyherdiansyahr@gmail.com<br>
                    <strong>Password:</strong> password123
                </small>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>