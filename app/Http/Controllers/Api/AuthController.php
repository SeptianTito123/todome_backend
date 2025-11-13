<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // Catatan: Dibutuhkan untuk enkripsi password
use Illuminate\Support\Facades\Auth; // Catatan: Dibutuhkan untuk proses login
use App\Models\User; // Catatan: Model User untuk membuat user baru

class AuthController extends Controller
{
    // ---------------------------------
    // FUNGSI UNTUK REGISTER USER BARU
    // ---------------------------------
    public function register(Request $request)
    {
        // 1. Validasi data yang masuk dari Flutter/Postman
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users', // Wajib unik di tabel users
            'password' => 'required|string|min:8|confirmed', // 'confirmed' akan mencari 'password_confirmation'
        ]);

        // 2. Buat user baru di database
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            // Catatan: Password WAJIB di-hash (enkripsi) sebelum disimpan
            'password' => Hash::make($validatedData['password']),
        ]);

        // 3. Kembalikan response sukses dalam format JSON
        return response()->json([
            'message' => 'User berhasil diregistrasi',
            'user' => $user,
        ], 201); // 201 = Created
    }

    // ---------------------------------
    // FUNGSI UNTUK LOGIN USER
    // ---------------------------------
    public function login(Request $request)
    {
        // 1. Validasi data yang masuk
        $credentials = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        // 2. Coba lakukan login (Auth::attempt)
        // Catatan: Ini otomatis mengecek email dan hash password
        if (!Auth::attempt($credentials)) {
            // 3. Jika gagal (email/password salah), kirim error 401
            return response()->json([
                'message' => 'Email atau Password salah'
            ], 401); // 401 = Unauthorized
        }

        // 4. Jika login berhasil, ambil data user
        // Catatan: Kita gunakan $request->user() BUKAN Auth::user()
        // karena kita perlu user dari instance request saat ini
        $user = $request->user();

        // 5. Buat Token API (Sanctum)
        // Catatan: Ini adalah "kunci digital" yang akan disimpan Flutter
        $token = $user->createToken('auth_token')->plainTextToken;

        // 6. Kembalikan response sukses (User + Token)
        return response()->json([
            'message' => 'Login berhasil',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200); // 200 = OK
    }
    // ---------------------------------
    // FUNGSI UNTUK LOGOUT USER
    // ---------------------------------
    public function logout(Request $request)
    {
        // Catatan: Mengambil user yang sedang login
        $user = $request->user();

        // Catatan: Menghapus token SAAT INI yang digunakan
        // Ini akan membuat token tersebut tidak valid lagi
        $user->currentAccessToken()->delete();

        // Kembalikan response sukses
        return response()->json([
            'message' => 'Berhasil logout'
        ], 200); // 200 = OK
    }
}