# Geotag Maker

Aplikasi Laravel untuk menambahkan koordinat **latitude** dan **longitude** ke dalam file CSV berdasarkan nama perusahaan/outlet, menggunakan **Google Places API (Text Search - New)**. Hasil CSV dirancang agar siap diimpor ke **Bitrix24** sebagai data companies beserta lokasinya.

Pengguna cukup mengunggah file CSV, menunggu proses geocoding selesai, lalu file hasil akan otomatis terunduh.

---

## Daftar Isi

- [Latar Belakang](#latar-belakang)
- [Fitur](#fitur)
- [Teknologi yang Digunakan](#teknologi-yang-digunakan)
- [Alur Kerja](#alur-kerja)
- [Struktur File Terkait](#struktur-file-terkait)
- [Instalasi](#instalasi)
- [Konfigurasi](#konfigurasi)
- [Cara Penggunaan](#cara-penggunaan)
- [Format CSV](#format-csv)
- [Detail Integrasi Google Places API](#detail-integrasi-google-places-api)
- [Testing](#testing)
- [Keamanan](#keamanan)
- [Keterbatasan Saat Ini](#keterbatasan-saat-ini)
- [Rencana Improvement di Masa Depan](#rencana-improvement-di-masa-depan)

---

## Latar Belakang

Sebelumnya proses geocoding dilakukan oleh script PHP mandiri (`kelantan.csv` → `kelantannew.csv`) yang berisi banyak hal campur dalam satu file: API key hardcoded, fungsi cURL, pembacaan CSV, pemanggilan API, dan penulisan CSV. Script tersebut sulit dirawat dan tidak aman.

Proyek ini adalah penulisan ulang (*rewrite*) script tersebut menjadi aplikasi Laravel yang terstruktur:

- Logika panggilan API dipisah ke dalam **Service Class**.
- Alur unggah/proses/unduh ditangani oleh **Controller**.
- Antarmuka pengguna dibuatkan **Blade View** dengan Tailwind CSS.
- API key dipindahkan dari kode ke **`.env`**.
- Ditambahkan **automated test**.

---

## Fitur

- Unggah file CSV melalui antarmuka web (drag & drop atau pilih file).
- Validasi file: wajib CSV.
- Deteksi otomatis kolom `Company Name`; jika tidak ada akan ditolak dengan pesan jelas.
- Deteksi dan pembersihan **UTF-8 BOM** pada header CSV.
- Penambahan kolom `latitude` dan `longitude` secara otomatis jika belum ada.
- Geocoding per baris melalui Google Places API.
- Baris tanpa `Company Name` akan dibiarkan kosong koordinatnya (tidak dipanggil API).
- Hasil diunduh langsung sebagai file `outlet-geotag.csv`.
- Error dari API Google dicatat ke log (tidak menggagalkan seluruh proses).
- Tampilan responsif dengan indikator loading (spinner) dan penanganan error di sisi klien.

---

## Teknologi yang Digunakan

| Komponen | Versi / Keterangan |
| --- | --- |
| PHP | ^8.2 |
| Laravel Framework | ^12.0 |
| HTTP Client | `Illuminate\Support\Facades\Http` (Guzzle) |
| Pest | ^3.8 (testing) |
| Laravel Pint | ^1.24 (code style) |
| Tailwind CSS | ^4.3 |
| Vite | ^7.0 |
| Database | SQLite (default) |

---

## Alur Kerja

```
[User] ──unggah CSV──▶ [GET /geotag: tampilkan form]
                              │
                              ▼
[POST /geotag] ──▶ GeotagCsvController@process
                              │
                  ┌───────────┴───────────┐
                  ▼                       ▼
        Baca header & validasi      Kolom "Company Name" ada?
        (BOM, trim, kolom)          │
                                    ▼
                     Untuk setiap baris:
                        Company Name kosong? ──ya──▶ koordinat dikosongkan
                                    │ tidak
                                    ▼
                        GooglePlaceService@geocodeAddress
                        (POST places:searchText) ──▶ ambil places[0].location
                                    │
                                    ▼
                     Tulis baris ke CSV output (storage/app/exports)
                                    │
                                    ▼
                     Kirim sebagai download "outlet-geotag.csv"
                     (file sementara dihapus setelah dikirim)
```

Keterangan tambahan:

- Setiap panggilan API diberi jeda `usleep(200000)` (0.2 detik) untuk mengurangi risiko rate limit.
- Waktu eksekusi tidak dibatasi (`set_time_limit(0)`) karena proses berjalan sinkron.
- File hasil sementara disimpan di `storage/app/exports/` lalu dihapus otomatis setelah diunduh (`deleteFileAfterSend(true)`).

---

## Struktur File Terkait

```
app/
├── Http/Controllers/GeotagCsvController.php   # create() & process()
└── Services/GooglePlaceService.php            # geocodeAddress()

config/
└── services.php                               # google_places.key

resources/views/
└── geotag/export.blade.php                    # Form + JavaScript (export)

routes/
└── web.php                                    # GET & POST /geotag

tests/Feature/
└── GeotagCsvTest.php                          # Feature test (Pest)

storage/app/exports/                           # File CSV hasil sementara
```

### Route

| Method | URI | Name | Action |
| --- | --- | --- | --- |
| GET | `/` | – | Halaman welcome default |
| GET | `/geotag` | `geotag.create` | `GeotagCsvController@create` |
| POST | `/geotag` | `geotag.process` | `GeotagCsvController@process` |

---

## Instalasi

Prasyarat: PHP 8.2+, Composer, Node.js & npm.

```bash
# 1. Install dependency PHP
composer install

# 2. Salin environment file & generate app key
copy .env.example .env
php artisan key:generate

# 3. Install dependency front-end & build asset
npm install
npm run build

# 4. Migrasi database (opsional, dipakai untuk session dll.)
php artisan migrate
```

> Ada juga shortcut: `composer run setup` untuk menjalankan langkah di atas sekaligus.

Untuk development, jalankan server:

```bash
composer run dev
```

atau secara manual:

```bash
php artisan serve
npm run dev
```

---

## Konfigurasi

Tambahkan API key Google Places pada `.env`:

```dotenv
GOOGLE_PLACES_KEY=your_google_places_api_key
```

Key ini dibaca melalui `config/services.php`:

```php
'google_places' => [
    'key' => env('GOOGLE_PLACES_KEY'),
],
```

dan diakses di service dengan `config('services.google_places.key')`.

> File `.env` sudah masuk `.gitignore`, sehingga **tidak akan ikut ter-commit**. Yang di-commit hanyalah `.env.example` (dengan nilai kosong).

Jika key belum diisi, service akan melempar error: `Google Places API Keys Belum dikonfigurasi`.

---

## Cara Penggunaan

1. Buka halaman `/geotag`.
2. Unggah file CSV (drag & drop ke area unggah, atau klik **Pilih File**).
3. Klik tombol **Export ke CSV**.
4. Tunggu proses selesai — file `outlet-geotag.csv` akan otomatis terunduh.
5. Impor file hasil ke Bitrix24.

---

## Format CSV

**File input** harus memiliki header dengan kolom `Company Name`. Contoh:

```csv
Company Name,Address,Phone
Kedai Kopi Ali,Jalan Besar,0123456789
Restoran Sederhana,Jalan Merdeka,
```

**File output** akan memiliki kolom `latitude` dan `longitude` yang ditambahkan di akhir (jika belum ada):

```csv
Company Name,Address,Phone,latitude,longitude
Kedai Kopi Ali,Jalan Besar,0123456789,6.123456,102.456789
Restoran Sederhana,Jalan Merdeka,,
```

Catatan:

- Baris yang jumlah kolomnya tidak sesuai dengan header akan dilewati.
- Baris tanpa `Company Name` akan diisi koordinat kosong.
- Kolom `formattedAddress` dari Google Places sudah diambil oleh service, tetapi **belum ditulis** ke CSV. Kolom ini bisa ditambahkan bila dibutuhkan.

---

## Detail Integrasi Google Places API

Service `GooglePlaceService::geocodeAddress()` memanggil **Places API (New) – Text Search**:

- **Endpoint**: `POST https://places.googleapis.com/v1/places:searchText`
- **Header**:
  - `Content-Type: application/json`
  - `X-Goog-Api-Key: <GOOGLE_PLACES_KEY>`
  - `X-Goog-FieldMask: places.location,places.formattedAddress`
  - `Referer: bonfood.dev.askarasoft.com`
- **Body**: `{ "textQuery": "<Company Name>" }`

Alur penanganan respons:

1. `ConnectionException` (koneksi gagal/timeout) → dicatat ke log, return `null`.
2. Respons gagal (status non-2xx) → pesan error dari API dicatat ke log, return `null`.
3. Mengambil hasil pertama (`places.0`). Jika `location.latitude`/`longitude` tidak ada → return `null`.
4. Jika sukses → return array `['lat', 'lng', 'address']`.

Jika service mengembalikan `null`, kolom `latitude` dan `longitude` untuk baris tersebut akan dikosongkan.

---

## Testing

Test menggunakan **Pest** dan mem-*fake* HTTP call ke Google, sehingga tidak memanggil API asli.

```bash
# Menjalankan test terkait geotag
php artisan test --compact --filter=GeotagCsv

# Menjalankan seluruh suite
php artisan test --compact
```

Cakupan test pada `tests/Feature/GeotagCsvTest.php`:

1. Mengisi `latitude` dan `longitude` berdasarkan respons Google Places.
2. Mengosongkan koordinat ketika tempat tidak ditemukan.
3. Menolak file yang tidak memiliki kolom `Company Name`.

### Code Style

```bash
vendor/bin/pint --format agent
```

---

## Keamanan

- **Jangan pernah** commit API key. Gunakan `.env` (sudah di-`.gitignore`).
- Batasi API key di Google Cloud Console. Karena request dilakukan **server-to-server**, pembatasan berbasis *HTTP referrer* tidak efektif — gunakan pembatasan berbasis **IP address** atau pembatasan API (khusus Places API).
- Bila API key pernah terekspos, segera **rotate** key tersebut.
- Hindari `git add -f .env`.

---

## Keterbatasan Saat Ini

- **Akurasi pencarian lokasi masih rendah.** Saat ini `textQuery` hanya menggunakan `Company Name` tanpa konteks alamat/wilayah, kemudian langsung mengambil hasil pertama (`places[0]`) tanpa proses pemeringkatan atau validasi kecocokan. Akibatnya koordinat bisa meleset, terutama untuk nama usaha yang umum atau ambigu. (Lihat bagian improvement di bawah.)
- **Hanya mendukung CSV.** Format Excel (`.xlsx`/`.xls`) belum diproses di sisi server.
- **Proses sinkron.** Untuk file dengan banyak baris, request HTTP bisa berjalan lama dan berisiko timeout di server produksi.
- **Tidak ada cache.** Nama perusahaan yang sama akan memanggil API berulang kali.
- **Kolom `address` belum ditulis** ke output walaupun sudah diambil dari API.
- **Header `Referer`** masih menyertakan domain `bonfood.dev.askarasoft.com`; sesuaikan atau hapus bila key tidak dibatasi referrer.

---

## Rencana Improvement di Masa Depan

### 1. Meningkatkan Akurasi Pencarian Lokasi (Prioritas Utama)

Hasil geocoding saat ini **belum seberapa akurat**. Berikut usulan perbaikan, dari yang paling sederhana hingga lanjutan:

**a. Manfaatkan konteks alamat, bukan hanya nama usaha**
- Gabungkan `Company Name` dengan kolom alamat/kota/negara bagian ke dalam `textQuery`, misalnya:
  `"<Company Name>, <Address>, <City>, <State>"`.
- Ini adalah perubahan dengan dampak akurasi paling besar.

**b. Gunakan parameter pembatas wilayah (region & lokasi)**
- Tambahkan `regionCode` (mis. `MY` untuk Malaysia) agar hasil condong ke negara yang benar.
- Gunakan `locationBias` (lingkaran: `circle.center` + `radius`) atau `locationRestriction` (kotak `rectangle`) untuk mempersempit pencarian ke area target (mis. sekitar Kelantan).
- Tambahkan `languageCode` bila ingin hasil berbahasa tertentu.

**c. Perbaiki pemilihan hasil (ranking), jangan asal ambil `places[0]`**
- Ambil beberapa kandidat hasil, lalu beri skor berdasarkan:
  - kemiripan nama (`similar_text` / Levenshtein) antara nama usaha dan `displayName`;
  - jarak koordinat kandidat terhadap titik pusat kota/area yang diharapkan;
  - kecocokan `types`/`includedType` (mis. `establishment`, `restaurant`, `store`).
- Pilih kandidat dengan skor tertinggi alih-alih hasil pertama.

**d. Tambahkan ambang batas kepercayaan (confidence threshold)**
- Bila skor kecocokan di bawah ambang tertentu, **jangan tulis koordinat** dan tandai baris tersebut untuk ditinjau manual (mis. kolom `geotag_status` = `needs_review`).
- Lebih baik kosong dan ditandai daripada salah lokasi.

**e. Normalisasi nama sebelum dikirim**
- Script lama membersihkan karakter khusus (`/ & % # @ . $ - +`) dari nama perusahaan sebelum dikirim. Logika ini belum diadopsi di controller baru — pertimbangkan untuk menambahkannya kembali agar query lebih bersih.

**f. Pertimbangkan Geocoding API untuk alamat**
- Bila data alamat lengkap tersedia, **Geocoding API** sering lebih tepat untuk mengonversi alamat menjadi koordinat, sedangkan Places Text Search lebih cocok untuk mencari nama tempat/usaha. Bisa digunakan secara kombinasi (Places dulu, fallback ke Geocoding).

**g. Simpan `formattedAddress` dan `place_id`**
- Tulis `formattedAddress` ke output agar hasil bisa diverifikasi mata, dan simpan `place_id` untuk memudahkan pengecekan ulang/audit.

**h. Sediakan alur verifikasi manual**
- Tampilkan pratinjau peta (mis. Google Maps embed) untuk membantu pengguna memverifikasi/mengoreksi koordinat sebelum diimpor ke Bitrix24.

### 2. Proses Asinkron (Queue Job)

- Pindahkan proses geocoding ke **Queued Job** agar tidak mengandalkan request sinkron dan terhindar dari timeout.
- Simpan hasil di storage, lalu beri tahu pengguna (link unduh/notifikasi) saat selesai.

### 3. Caching & Efisiensi Biaya

- Simpan hasil geocoding per nama/alamat di cache/database untuk menghindari panggilan API berulang dan mengurangi biaya.
- Tambahkan mekanisme retry dengan backoff untuk kegagalan sementara dan penanganan rate limit yang lebih baik.

### 4. Dukungan Format Lain

- Tambahkan dukungan unggah `.xlsx`/`.xls` (mis. dengan `maatwebsite/excel`).
- Dukung pemilihan kolom sumber (kolom mana yang dipakai sebagai nama/alamat) agar lebih fleksibel.

### 5. Operasional & Kualitas

- Tambahkan kolom status (`geotag_status`) pada output untuk menandai baris yang gagal/perlu ditinjau.
- Tambahkan logging ringkasan proses (jumlah baris sukses/gagal) dan opsi membatalkan proses.
- Perluas cakupan test (kasus API error, timeout, baris tanpa Company Name, dsb.).

---

## Lisensi

Proyek internal **Askarasoft**. Lihat file `composer.json` untuk informasi lisensi.
