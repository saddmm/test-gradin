# Courier Management RESTful API

Implementasi backend technical test menggunakan **Laravel 13 RESTful API** dengan penerapan prinsip SOLID, Query Scopes, Form Requests, API Resources, pengujian otomatis lengkap (PHPUnit & Newman), serta Docker container menggunakan **Laravel Sail** dan **MySQL 8.4**.

---

## 🛠️ Teknologi & Kebutuhan Sistem

- **Framework**: Laravel 13
- **PHP**: PHP 8.5 (di dalam container Laravel Sail)
- **Database**: MySQL 8.4 (di dalam container Laravel Sail)
- **Containerization**: Laravel Sail (Docker Compose)
- **Testing**: PHPUnit dengan `RefreshDatabase` & Newman (Postman test runner)
- **Standar Kode**: PSR-12 (Laravel Pint)

---

## 🚀 Panduan Memulai Cepat (Laravel Sail)

### 1. Konfigurasi Environment

Salin file environment:
```bash
cp .env.example .env
```

Konfigurasi database dan port Sail pada file `.env` telah disesuaikan:
```env
APP_PORT=8000
VITE_PORT=5174
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=courier_db
DB_USERNAME=courier_user
DB_PASSWORD=password
```

### 2. Jalankan Container Laravel Sail

Nyalakan container di latar belakang:
```bash
./vendor/bin/sail up -d
```

### 3. Jalankan Migrasi Database

Jalankan perintah migrasi tabel:
```bash
./vendor/bin/sail artisan migrate
```

API siap diakses pada: `http://localhost:8000/api/couriers`

---

## 🧪 Pengujian Otomatis (Automated Tests)

### 1. PHPUnit Feature Tests (Laravel)

Jalankan seluruh rangkaian test di dalam container Sail:
```bash
./vendor/bin/sail test
```

Atau jalankan spesifik untuk test kurir:
```bash
./vendor/bin/sail test --filter=CourierTest
```

#### Skenario Pengujian yang Dicakup:
1. `test_index_returns_paginated_couriers_with_default_per_page_and_sort_by_name_asc`: Memverifikasi pagination bawaan (15 data per halaman) dan pengurutan default nama ascending (A-Z).
2. `test_index_can_override_sorting_by_registered_at_desc`: Memverifikasi override sorting melalui `?sort_by=registered_at&sort_dir=desc`.
3. `test_index_multi_word_tokenized_search_matches_and_excludes_non_matches`: Memverifikasi pencarian multi-token dengan logika AND (misal `?search=budi+agung` cocok dengan "Budiono Hadi Agung" dan "Agung Budi Prasetyo", namun mengabaikan kurir yang hanya memiliki salah satu kata).
4. `test_index_filters_by_comma_separated_levels`: Memverifikasi parsing string level dipisahkan koma `?level=2,3` menjadi array dan pemfilteran menggunakan `whereIn`.
5. `test_show_returns_single_courier`: Memverifikasi response 200 OK dan struktur data tunggal kurir.
6. `test_show_returns_404_when_not_found`: Memverifikasi response 404 Not Found ketika ID kurir tidak ditemukan.
7. `test_store_saves_valid_courier_to_database`: Memverifikasi response 201 Created dan data berhasil disimpan ke database (`assertDatabaseHas`).
8. `test_store_rejects_invalid_data_such_as_invalid_level_or_missing_fields`: Memverifikasi response 422 Unprocessable Content untuk input tidak valid (misal level > 5 atau tipe kendaraan di luar enum).
9. `test_update_modifies_courier_in_database`: Memverifikasi response 200 OK dan pembaruan data di database.
10. `test_update_rejects_duplicate_email_belonging_to_another_courier`: Memverifikasi validasi unique email yang mengabaikan ID kurir yang sedang diupdate.
11. `test_destroy_deletes_courier_from_database`: Memverifikasi penghapusan kurir dari database (`assertDatabaseMissing`).

### 2. Pengujian Postman Collection (Newman)

Semua request pada Postman Collection telah dilengkapi skrip pengujian otomatis (test assertions & prerequest scripts). Jalankan via Newman:

```bash
npx newman run postman_collection.json --env-var "base_url=http://localhost:8000"
```

---

## 📬 Postman Collection

File Postman collection v2.1 tersedia langsung di root repository:
- **Nama File**: `postman_collection.json`
- **Variabel bawaan**: `base_url` diset ke `http://localhost:8000`
- **Fitur Otomatis**: Dilengkapi test scripts JavaScript (verifikasi status code 200/201/404/422, validasi struktur payload JSON, dan penyimpanan dynamic ID untuk pengujian berantai dari create hingga delete).

---

## 📚 Dokumentasi Endpoint API

Base URL: `http://localhost:8000/api/couriers`

### 1. Daftar Kurir (GET `/api/couriers`)

Mengambil daftar kurir berhalaman dengan dukungan pencarian multi-kata, filter level, dan pengurutan dinamis.

#### Parameter Query:
| Parameter | Tipe | Default | Deskripsi |
|---|---|---|---|
| `search` | string | - | Pencarian token nama dengan logika AND (contoh: `?search=budi+agung`). |
| `level` | string | - | Filter level 1 s.d. 5 dipisahkan koma (contoh: `?level=2,3`). |
| `sort_by` | string | `name` | Kolom urutan: `name`, `registered_at`, `created_at`, `level`, `code`. |
| `sort_dir` | string | `asc` | Arah urutan: `asc` atau `desc`. |
| `per_page` | integer | `15` | Jumlah data per halaman (1 s.d. 100). |
| `page` | integer | `1` | Nomor halaman pagination. |

#### Contoh Request cURL:
```bash
curl -X GET "http://localhost:8000/api/couriers?search=budi+agung&level=2,3&sort_by=registered_at&sort_dir=desc&per_page=10" \
  -H "Accept: application/json"
```

#### Contoh Response (200 OK):
```json
{
  "data": [
    {
      "id": 1,
      "code": "CR-0001",
      "name": "Budiono Hadi Agung",
      "email": "budiono@example.com",
      "phone": "+6281234567890",
      "vehicle_type": "motorcycle",
      "license_plate": "B 1234 ABC",
      "level": 3,
      "is_active": true,
      "registered_at": "2026-03-15T08:30:00.000000Z",
      "created_at": "2026-03-15T08:30:00.000000Z",
      "updated_at": "2026-03-15T08:30:00.000000Z"
    }
  ],
  "links": {
    "first": "http://localhost:8000/api/couriers?page=1",
    "last": "http://localhost:8000/api/couriers?page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "per_page": 10,
    "to": 1,
    "total": 1
  }
}
```

---

### 2. Tambah Kurir Baru (POST `/api/couriers`)

Menambahkan data kurir baru ke dalam sistem.

#### Header:
`Content-Type: application/json`  
`Accept: application/json`

#### Request Body:
| Field | Tipe | Aturan Validasi |
|---|---|---|
| `code` | string | Required, max 20 karakter, unik di tabel couriers |
| `name` | string | Required, max 255 karakter |
| `email` | string | Required, format email valid, max 255 karakter, unik di tabel couriers |
| `phone` | string | Required, max 20 karakter, unik di tabel couriers |
| `vehicle_type` | string | Required, enum: `motorcycle`, `car`, `van`, `truck` |
| `license_plate` | string | Required, max 15 karakter |
| `level` | integer | Required, integer antara 1 sampai 5 |
| `is_active` | boolean | Opsional (default: `true`) |
| `registered_at` | datetime | Opsional (default: waktu sekarang) |

#### Contoh Request cURL:
```bash
curl -X POST "http://localhost:8000/api/couriers" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "code": "CR-0002",
    "name": "Siti Rahma",
    "email": "siti.rahma@example.com",
    "phone": "+6281987654321",
    "vehicle_type": "car",
    "license_plate": "B 5678 DEF",
    "level": 2,
    "is_active": true
  }'
```

#### Response (201 Created):
```json
{
  "data": {
    "id": 2,
    "code": "CR-0002",
    "name": "Siti Rahma",
    "email": "siti.rahma@example.com",
    "phone": "+6281987654321",
    "vehicle_type": "car",
    "license_plate": "B 5678 DEF",
    "level": 2,
    "is_active": true,
    "registered_at": "2026-11-02T13:45:00.000000Z",
    "created_at": "2026-11-02T13:45:00.000000Z",
    "updated_at": "2026-11-02T13:45:00.000000Z"
  }
}
```

---

### 3. Detail Kurir (GET `/api/couriers/{id}`)

Melihat detail satu kurir berdasarkan ID.

#### Contoh Request cURL:
```bash
curl -X GET "http://localhost:8000/api/couriers/1" \
  -H "Accept: application/json"
```

#### Response (200 OK):
```json
{
  "data": {
    "id": 1,
    "code": "CR-0001",
    "name": "Budiono Hadi Agung",
    "email": "budiono@example.com",
    "phone": "+6281234567890",
    "vehicle_type": "motorcycle",
    "license_plate": "B 1234 ABC",
    "level": 3,
    "is_active": true,
    "registered_at": "2026-03-15T08:30:00.000000Z",
    "created_at": "2026-03-15T08:30:00.000000Z",
    "updated_at": "2026-03-15T08:30:00.000000Z"
  }
}
```

---

### 4. Perbarui Kurir (PUT/PATCH `/api/couriers/{id}`)

Memperbarui data kurir (mendukung pembaruan parsial).

#### Contoh Request cURL:
```bash
curl -X PUT "http://localhost:8000/api/couriers/1" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "Budiono Hadi Agung Perkasa",
    "level": 4,
    "vehicle_type": "car"
  }'
```

#### Response (200 OK):
```json
{
  "data": {
    "id": 1,
    "code": "CR-0001",
    "name": "Budiono Hadi Agung Perkasa",
    "email": "budiono@example.com",
    "phone": "+6281234567890",
    "vehicle_type": "car",
    "license_plate": "B 1234 ABC",
    "level": 4,
    "is_active": true,
    "registered_at": "2026-03-15T08:30:00.000000Z",
    "created_at": "2026-03-15T08:30:00.000000Z",
    "updated_at": "2026-11-02T13:50:00.000000Z"
  }
}
```

---

### 5. Hapus Kurir (DELETE `/api/couriers/{id}`)

Menghapus data kurir secara permanen dari sistem.

#### Contoh Request cURL:
```bash
curl -X DELETE "http://localhost:8000/api/couriers/1" \
  -H "Accept: application/json"
```

#### Response (200 OK):
```json
{
  "message": "Courier deleted successfully"
}
```

---

## 🏛️ Arsitektur & Prinsip Clean Code

1. **Prinsip SOLID**:
   - **Single Responsibility Principle (SRP)**: Controller hanya bertugas mengarahkan alur request/response; validasi didelegasikan ke Form Requests (`StoreCourierRequest`, `UpdateCourierRequest`), transformasi output ke `CourierResource`, dan logika filter/pencarian ke Eloquent Scopes.
   - **Open/Closed Principle (OCP)**: Penambahan kriteria pencarian atau filter baru dilakukan melalui penambahan model scope tanpa memodifikasi logika query dasar.
   - **Liskov Substitution & Dependency Inversion (LSP & DIP)**: Memanfaatkan kontrak resmi Laravel (Eloquent Builder, FormRequest, JsonResource).

2. **Standar PSR-12**:
   - Kode diformat rapi dan divalidasi dengan Laravel Pint.

3. **Optimasi Database & Indexing**:
   - Kolom `code`, `email`, dan `phone` memiliki unique constraint.
   - Kolom `name`, `level`, dan `registered_at` diberi indeks database untuk efisiensi eksekusi pencarian, pemfilteran, dan sorting data dalam skala besar.
