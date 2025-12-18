# 🎓 Journey Learn - Learning Management System (LMS)

**Journey Learn** adalah aplikasi sistem manajemen pembelajaran berbasis web yang dirancang untuk memfasilitasi proses belajar mengajar secara daring. Aplikasi ini memungkinkan interaksi antara Dosen dan Mahasiswa melalui manajemen kelas yang terstruktur, penyampaian materi (video dan teks), serta evaluasi pembelajaran melalui kuis interaktif.

Aplikasi ini dikembangkan dengan mengutamakan pengalaman pengguna (User Experience), keamanan data, dan alur pembelajaran yang sistematis (*Sequential Learning*).

---

## 🌟 Fitur Utama

### 1. Sistem Multi-Role
- **Dosen (Instructor):** Dapat membuat kelas, mengelola materi (Video, Teks, Quiz), memantau jumlah mahasiswa, dan mengatur alur pembelajaran.
- **Mahasiswa (Student):** Dapat mendaftar kelas (*enrollment*), mengakses materi, mengerjakan kuis, dan melacak progress belajar via dashboard.

### 2. Sequential Learning (Alur Belajar Berurutan)
Fitur unggulan yang memastikan mahasiswa harus menyelesaikan materi secara berurutan.
- Materi terkunci (*locked*) secara default.
- Materi berikutnya baru terbuka otomatis setelah materi sebelumnya diselesaikan atau lulus kuis.

### 3. Manajemen Materi Multimedia
- **Video:** Integrasi seamless dengan YouTube Embed.
- **Rich Text:** Modul pembelajaran berbasis teks HTML.
- **Quiz Interaktif:** Evaluasi pilihan ganda dengan sistem penilaian otomatis.

### 4. Sistem Evaluasi & Tracking
- **Quiz Logic:** Penilaian otomatis, review jawaban benar/salah, dan batasan percobaan (*Attempt Limit*) untuk mencegah kecurangan.
- **Progress Tracking:** Dashboard visual yang menampilkan persentase penyelesaian kelas.
- **Status Indikator:** Penanda visual untuk materi *Completed*, *In Progress*, dan *Locked*.

### 5. Keamanan & Performa
- **Secure Authentication:** Password hashing (Bcrypt), session security, dan proteksi Session Fixation.
- **Data Integrity:** Penggunaan Prepared Statements untuk mencegah SQL Injection.
- **XSS Protection:** Output escaping pada konten text.

---

## 🛠️ Teknologi yang Digunakan

- **Backend:** PHP 8 (Native)
- **Database:** MySQL / MariaDB
- **Frontend:** HTML5, CSS3, Bootstrap 5 (Responsive Design)
- **Server:** Apache (via XAMPP)

---

## 📂 Struktur Database

Aplikasi ini menggunakan basis data relasional dengan tabel-tabel berikut:

```sql
-- 1. Users: Menyimpan data pengguna
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('dosen', 'mahasiswa') NOT NULL,
    foto_profil VARCHAR(255) DEFAULT 'default.jpg',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Kelas: Menyimpan data kelas/kursus
CREATE TABLE kelas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dosen_id INT NOT NULL,
    nama_kelas VARCHAR(255) NOT NULL,
    deskripsi TEXT,
    kategori VARCHAR(50),
    total_courses INT DEFAULT 0, -- Opsional
    tanggal_dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (dosen_id) REFERENCES users(id)
);

-- 3. Enrollment: Mencatat pendaftaran mahasiswa
CREATE TABLE enrollment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    kelas_id INT NOT NULL,
    tanggal_enroll TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (kelas_id) REFERENCES kelas(id)
);

-- 4. Materi: Konten pembelajaran
CREATE TABLE materi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kelas_id INT NOT NULL,
    judul VARCHAR(255) NOT NULL,
    tipe ENUM('video', 'text', 'quiz') NOT NULL,
    konten TEXT,
    urutan INT NOT NULL,
    durasi_menit INT DEFAULT 0,
    tanggal_dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (kelas_id) REFERENCES kelas(id)
);

-- 5. Progress: Melacak status belajar
CREATE TABLE progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    materi_id INT NOT NULL,
    status ENUM('locked', 'in_progress', 'completed') DEFAULT 'locked',
    tanggal_mulai DATETIME,
    tanggal_selesai DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (materi_id) REFERENCES materi(id)
);

-- 6. Kuis: Bank soal
CREATE TABLE kuis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    materi_id INT NOT NULL,
    pertanyaan TEXT NOT NULL,
    pilihan_a VARCHAR(255) NOT NULL,
    pilihan_b VARCHAR(255) NOT NULL,
    pilihan_c VARCHAR(255) NOT NULL,
    pilihan_d VARCHAR(255) NOT NULL,
    jawaban_benar CHAR(1) NOT NULL, -- A, B, C, atau D
    poin INT DEFAULT 10,
    FOREIGN KEY (materi_id) REFERENCES materi(id)
);

-- 7. Hasil Kuis: Rekap nilai
CREATE TABLE hasil_kuis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    materi_id INT NOT NULL,
    skor INT NOT NULL,
    total_soal INT NOT NULL,
    jawaban_detail JSON,
    tanggal_dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (materi_id) REFERENCES materi(id)
);
```

---

## 🚀 Cara Instalasi

1.  **Clone / Download Project:**
    Simpan folder proyek `LMS` ke dalam direktori server lokal Anda (contoh: `C:\xampp\htdocs\LMS`).

2.  **Setup Database:**
    - Buka phpMyAdmin (http://localhost/phpmyadmin).
    - Buat database baru dengan nama `db_elearning`.
    - Import skema SQL di atas atau file `.sql` jika tersedia.

3.  **Konfigurasi Koneksi:**
    - Buka file `config.php`.
    - Sesuaikan pengaturan database jika diperlukan:
      ```php
      define('DB_HOST', 'localhost');
      define('DB_USER', 'root');
      define('DB_PASS', ''); 
      define('DB_NAME', 'db_elearning');
      define('BASE_URL', 'http://localhost/LMS/');
      ```

4.  **Jalankan Aplikasi:**
    - Buka browser dan akses: `http://localhost/LMS/`

---

## 📄 Lisensi

Project ini dibuat untuk keperluan tugas akademik / pengembangan edukasi.
Codebase bebas digunakan dan dimodifikasi untuk tujuan pembelajaran.
