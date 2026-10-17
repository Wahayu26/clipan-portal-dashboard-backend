# SOC Dashboard Portal Backend (REST API)

Layanan backend mandiri (*dedicated backend microservice*) untuk **Clipan Portal Dashboard** — PT Clipan Finance Indonesia Tbk.

Dibangun menggunakan **Lightweight Modular PHP 8.3 REST API** tanpa beban framework berat (*zero heavy dependencies*), berkecepatan tinggi, aman, dan dirancang khusus untuk menangani autentikasi SSO, manajemen sesi, proteksi brute-force, serta registrasi aplikasi portal.

---

## 🌟 Fitur Utama

1. **Autentikasi & Session Management**:
   - Mendukung sesi login standar (12 Jam) dan perpanjangan sesi *Remember Me* (7 Hari).
   - Menghasilkan token bearer kriptografis aman (64 karakter hex) yang diverifikasi di setiap request.
   - Mengatur cookie sesi pendamping (`soc_auth=1`) untuk integrasi Single Sign-On (SSO) instan ke Sentinel SOC Command Center.

2. **Proteksi Anti Brute-Force (Rate Limiting)**:
   - Membatasi maksimal **5 kali percobaan login gagal**.
   - Menampilkan peringatan dinamis saat sisa kesempatan $\le 3$.
   - Mengunci akun secara otomatis selama **60 detik** jika gagal 5 kali berturut-turut, disertai respon waktu hitung mundur (*cooldown timer*).

3. **Portal Applications Registry**:
   - Menyimpan seluruh 40+ aplikasi internal Clipan ke dalam tabel database MySQL `portal_apps`.
   - Fitur PIN / Favorit aplikasi tersimpan secara permanen di database.
   - Fitur telemetri penghitung peluncuran aplikasi (*launch counter*) dan riwayat waktu terakhir dibuka.
   - Endpoint untuk mendaftarkan aplikasi baru (*custom application*).

4. **Audit Trail & Activity Logging**:
   - Setiap aktivitas login berhasil, login gagal, akun terkunci, peluncuran aplikasi, dan penambahan aplikasi dicatat di tabel `portal_audit_logs`.

---

## 👤 Akun Bawaan (Default Accounts)

| No | Username | Password | Role | Nama Lengkap |
|:---:|:---|:---|:---:|:---|
| 1 | `Admin` | `Cl1p4nN4dm1n123!` | **Super User** | Administrator |
| 2 | `wahyu.aji` | `Cl1p4nNUs3r123!` | **Admin** | Wahyu Aji |
| 3 | `dwi.salindri` | `Cl1p4nNUs3r123!` | **Admin** | Dwi Salindri |
| 4 | `satria.finandar` | `Cl1p4nNUs3r123!` | **Admin** | Satria Finandar |

---

## 🔌 Daftar Endpoint REST API

| Method | Endpoint | Auth | Deskripsi |
|---|---|:---:|---|
| `GET` | `/api/health` | Public | Health check status database dan server |
| `POST` | `/api/login` | Public | Login akun dengan proteksi anti brute-force |
| `POST` | `/api/logout` | Optional | Invalidate token dan logout sesi |
| `GET` | `/api/me` | Bearer | Mendapatkan detail profil user yang sedang login |
| `GET` | `/api/verify-session` | Bearer | Memverifikasi apakah token sesi masih aktif |
| `GET` | `/api/apps` | Public | Mengambil daftar seluruh aplikasi & tools portal |
| `POST` | `/api/apps` | Bearer | Mendaftarkan aplikasi baru ke portal |
| `POST` | `/api/apps/:id/favorite` | Public | Menandai / menghapus tanda bintang (Favorite) |
| `POST` | `/api/apps/:id/launch` | Public | Menambah counter peluncuran aplikasi |
| `DELETE`| `/api/apps/:id` | Bearer | Menghapus aplikasi kustom |
| `GET` | `/api/activities` | Public | Riwayat aktivitas dan log peluncuran aplikasi |

---

## 🚀 Cara Menjalankan & Deployment

### Opsi A: Menggunakan Docker (Disarankan di Server)

Container berjalan di port **8091**.

```bash
# 1. Salin environment
cp .env.example .env

# 2. Jalankan container
docker compose up -d --build

# 3. Jalankan migrasi dan seed database di dalam container
docker compose exec portal-backend php database/migrate.php
docker compose exec portal-backend php database/seed.php
```

### Opsi B: Menggunakan PHP Lokal / Apache

```bash
# 1. Jalankan migrasi & seed
php database/migrate.php
php database/seed.php

# 2. Jalankan server lokal di port 8091
php -S 0.0.0.0:8091 -t public public/index.php
```

---

## 🌐 Integrasi Apache Reverse Proxy di `soc-dashboard-proxy.conf`

Cukup tambahkan blok berikut di Apache `/etc/httpd/conf.d/soc-dashboard-proxy.conf`:

```apache
# Proxy REST API Portal ke Backend Port 8091
ProxyPass /api/portal/ http://127.0.0.1:8091/api/
ProxyPassReverse /api/portal/ http://127.0.0.1:8091/api/
```
