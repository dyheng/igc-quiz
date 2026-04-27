# IGC Quiz

Aplikasi quiz interaktif real-time dengan Laravel 11, Livewire, Tailwind CSS, Alpine.js, dan Laravel Reverb (WebSocket self-hosted).

## Fitur

- Login admin dengan password tunggal (tanpa username, tanpa tabel users).
- Manajemen quiz: judul + tambah/edit/hapus pertanyaan beserta opsi jawaban (pilih satu jawaban benar).
- Counter "X soal" yang update real-time setelah tiap pertanyaan disimpan.
- "Prepare Quiz" membuat sesi baru dengan kode unik & link share untuk peserta (model multi-sesi).
- Peserta gabung hanya dengan input nama (tanpa login). Halaman waiting otomatis sinkron saat admin Start.
- Dashboard admin live: jumlah peserta join, jumlah jawaban benar/salah per peserta, progress bar.
- Timer countdown sisi peserta dengan warna kritis di < 30 dan < 10 detik.
- Tombol Stop Quiz mengakhiri sesi lebih cepat; semua peserta otomatis pindah ke halaman summary.
- Peserta yang menekan "Selesai" sebelum waktu habis juga langsung melihat summary.
- UI minimalist & elegan, mobile-first, responsif (diuji dari layar 360px).

## Stack

- Laravel 11, Livewire 4 (forward-compatible dengan API Livewire 3), Tailwind CSS 3, Alpine.js (bundled by Livewire), MySQL/MariaDB.
- Realtime: Laravel Reverb self-hosted (WebSocket berbasis protokol Pusher) + `laravel-echo` + `pusher-js`. **Tidak butuh akun pusher.com.**

## Persyaratan

- PHP >= 8.2 (project diuji pada PHP 8.3)
- Composer
- Node.js >= 18 (project diuji pada Node 22)
- MySQL atau MariaDB

## Instalasi

```bash
git clone <repo> igc-quiz
cd igc-quiz
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Buat database `igc-quiz`:

```sql
CREATE DATABASE `igc-quiz` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Konfigurasi `.env` (default sudah cocok untuk MariaDB/MySQL lokal `root` tanpa password):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=igc-quiz
DB_USERNAME=root
DB_PASSWORD=
BROADCAST_CONNECTION=reverb
```

Migrasi & seed (seeder mengisi 1 quiz contoh "Quiz Pengetahuan Umum"):

```bash
php artisan migrate
php artisan db:seed
```

Set password admin (default seeder pakai `admin123`, ubah jika perlu):

```bash
php artisan admin:set-password admin123
```

Build assets:

```bash
npm run build
```

## Menjalankan (development)

Butuh **3 proses** berjalan paralel:

```bash
# Terminal 1 — web server
php artisan serve

# Terminal 2 — WebSocket server (Reverb)
php artisan reverb:start

# Terminal 3 — Vite dev server
npm run dev
```

Akses:

- Admin: `http://127.0.0.1:8000/admin/login` (password: yang Anda set di atas)
- Peserta: link diberikan oleh admin setelah klik "Prepare Quiz"

## Alur penggunaan

1. Admin login -> daftar quiz.
2. Admin "Tambah Quiz" -> isi judul -> tambahkan pertanyaan satu per satu (minimal 2 opsi, pilih 1 jawaban benar). Counter "X soal" update setiap simpan.
3. Di daftar quiz, admin tekan **Prepare Quiz** -> halaman sesi terbuka, link peserta + input durasi tampil.
4. Admin bagikan link ke peserta. Peserta input nama -> halaman waiting (sinkron via WebSocket).
5. Saat semua peserta siap, admin set durasi (menit) dan tekan **Start Quiz**.
   - Semua peserta auto-pindah ke halaman pertanyaan dengan timer countdown.
   - Dashboard admin menampilkan jawaban benar/salah live per peserta.
6. Peserta selesaikan quiz dengan tekan **Selesai** atau timer habis.
7. Admin bisa tekan **Stop Quiz** kapan saja; semua peserta langsung pindah ke summary.

## Reset password admin

```bash
php artisan admin:set-password <password-baru>
```

(Password disimpan sebagai bcrypt hash di `.env` sebagai `ADMIN_PASSWORD_HASH`.)

## Catatan teknis

- Channel WebSocket: `session.{id}` (public). Peserta tidak punya auth Laravel, jadi public channel digunakan. Otorisasi action sensitif (Start/Stop/Submit) tetap di-enforce di server.
- Event di-broadcast dengan `ShouldBroadcastNow` (sinkron) sehingga tidak butuh queue worker terpisah.
- Polling fallback `wire:poll` 3-10 detik dipasang di komponen yang menerima event Reverb sebagai pengaman jika koneksi WebSocket terputus.
- Tabel default `users`/`password_reset_tokens` dipertahankan tetapi tidak digunakan (idle).

## Struktur direktori utama

```
app/
  Console/Commands/SetAdminPassword.php
  Events/                 # 5 event broadcasting
  Http/
    Controllers/
      AdminAuthController.php
      PrepareQuizController.php
    Middleware/
      EnsureAdmin.php
      RedirectIfAdmin.php
  Livewire/
    AdminLogin.php
    QuizList.php
    QuizEditor.php
    RunSession.php
    JoinQuiz.php
    PlayQuiz.php
  Models/
    Quiz.php, Question.php, QuestionOption.php,
    QuizSession.php, Participant.php, ParticipantAnswer.php
config/quiz.php
resources/
  css/app.css             # Tailwind + design tokens (palette, komponen)
  js/echo.js              # konfigurasi Echo -> Reverb
  views/
    components/ui/        # Blade UI components (button, card, input, badge, modal, toast, empty-state)
    layouts/app.blade.php
    livewire/             # 6 view per komponen Livewire
routes/web.php
```

## Lisensi

MIT.
