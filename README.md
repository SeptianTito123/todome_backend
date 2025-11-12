# ---------------------------------
# Catatan Setup Backend (todome_backend)
# ---------------------------------

Ini adalah project REST API Laravel untuk aplikasi mobile ToDoMe.
API ini mengelola user, tasks, categories, dan menyediakan data statistik.

## 1. Persyaratan Environment (Wajib)

Pastikan environment Laragon Anda memenuhi syarat berikut:
* **PHP:** Versi `8.2` atau lebih tinggi.
* **Database:** MySQL (Bawaan Laragon).
* **Composer:** Terinstal (Bawaan Laragon).

## 2. Langkah Instalasi (untuk Tim)

Langkah-langkah ini untuk siapa saja yang baru bergabung dan ingin menjalankan project di komputer mereka.

1.  **Clone Repository:**
    ```bash
    git clone [https://github.com/SeptianTito123/todome_backend.git](https://github.com/SeptianTito123/todome_backend.git)
    cd todome_backend
    ```

2.  **Buat Database:**
    * Buka Laragon -> Database.
    * Buat database MySQL baru dengan nama `todome_db`.

3.  **Setup File .env:**
    * Salin file `.env.example` menjadi `.env`:
        ```bash
        cp .env.example .env
        ```
    * Edit file `.env` dan sesuaikan pengaturan database:
        ```
        DB_DATABASE=todome_db
        DB_USERNAME=root
        DB_PASSWORD=
        ```

4.  **Install Dependencies (Composer):**
    ```bash
    # Catatan: Menginstal semua dependensi dari composer.lock
    composer install
    ```

5.  **Hasilkan Kunci Aplikasi (Generate Key):**
    ```bash
    php artisan key:generate
    ```

6.  **Jalankan Migrasi Database:**
    * Perintah ini akan membuat SEMUA tabel yang diperlukan:
    * `users` (bawaan)
    * `personal_access_tokens` (dari Sanctum)
    * `tasks` (dengan relasi user)
    * `categories` (dengan relasi user)
    * `category_task` (tabel pivot many-to-many)
    ```bash
    # Catatan: Menjalankan semua file migrasi
    php artisan migrate
    ```

7.  **Jalankan Server:**
    ```bash
    php artisan serve
    ```
    API sekarang berjalan di `http://127.0.0.1:8000`.

## 3. Package Tambahan yang Diinstal

* **`laravel/sanctum`:**
    * Digunakan untuk autentikasi API (login/register/token).
    * *Catatan instalasi (sudah dilakukan):*
        1.  `composer require laravel/sanctum`
        2.  `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`
        3.  `php artisan migrate`
        4.  Model `User.php` sudah di-update dengan trait `HasApiTokens`.

---

## 4. Rangkuman API Endpoint

**Base URL:** `http://127.0.0.1:8000/api`

> **PENTING:** Semua endpoint (kecuali `/register` dan `/login`) **WAJIB** diamankan. Kirimkan `access_token` (didapat dari Login) sebagai **Bearer Token** di header `Authorization`.

---

### 4.1. Autentikasi (AuthController)

| Metode | Endpoint | Deskripsi | Wajib Token? |
| :--- | :--- | :--- | :--- |
| `POST` | `/register` | Mendaftarkan user baru. (Body: `name`, `email`, `password`, `password_confirmation`) | **Tidak** |
| `POST` | `/login` | Login user. (Body: `email`, `password`). Mengembalikan `access_token`. | **Tidak** |
| `POST` | `/logout` | Logout user (menghapus token saat ini). | **Ya** |

---

### 4.2. Tasks (TaskController)

| Metode | Endpoint | Deskripsi | Wajib Token? |
| :--- | :--- | :--- | :--- |
| `GET` | `/tasks` | Mendapat **semua** task milik user (termasuk data kategorinya). | **Ya** |
| `POST` | `/tasks` | Membuat task baru. (Body: `judul`, `deskripsi` (ops), `deadline` (ops), `category_ids` (ops, array `[1, 2]`)). | **Ya** |
| `GET` | `/tasks/{id}` | Melihat detail satu task (termasuk data kategorinya). | **Ya** |
| `PUT` | `/tasks/{id}` | Update satu task. (Body: `judul`, `status_selesai`, `category_ids` (ops, array), dll). | **Ya** |
| `DELETE`| `/tasks/{id}` | Menghapus satu task. | **Ya** |

---

### 4.3. Categories (CategoryController)

| Metode | Endpoint | Deskripsi | Wajib Token? |
| :--- | :--- | :--- | :--- |
| `GET` | `/categories` | Mendapat **semua** kategori milik user. | **Ya** |
| `POST` | `/categories` | Membuat kategori baru. (Body: `name`). | **Ya** |
| `GET` | `/categories/{id}` | Melihat detail satu kategori. | **Ya** |
| `PUT` | `/categories/{id}` | Update satu kategori. (Body: `name`). | **Ya** |
| `DELETE`| `/categories/{id}` | Menghapus satu kategori. | **Ya** |

---

### 4.4. Dashboard & Kalender (DashboardController)

| Metode | Endpoint | Deskripsi | Wajib Token? |
| :--- | :--- | :--- | :--- |
| `GET` | `/dashboard/summary` | Mendapat data ringkasan untuk profile (total, list, charts). | **Ya** |
| `GET` | `/calendar/tasks` | Mendapat tasks berdasarkan tanggal. (Wajib Params: `?date=YYYY-MM-DD`). | **Ya** |