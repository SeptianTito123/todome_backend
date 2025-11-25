<?php

namespace App\Http\Controllers\Api;
use Illuminate\Auth\Events\Registered;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // Catatan: Dibutuhkan untuk enkripsi password
use Illuminate\Support\Facades\Auth; // Catatan: Dibutuhkan untuk proses login
use App\Models\User; // Catatan: Model User untuk membuat user baru
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    // ---------------------------------
    // FUNGSI UNTUK REGISTER USER BARU
    // ---------------------------------
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // --- MULAI TRANSAKSI ---
        DB::beginTransaction();

        try {
            // 1. Buat User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // 2. Kirim Email
            event(new Registered($user));

            // 3. Jika sampai sini aman, Simpan Permanen
            DB::commit();

            return response()->json(['message' => 'Registrasi berhasil. Silakan cek email untuk verifikasi.'], 201);

        } catch (\Exception $e) {
            // 4. JIKA ADA ERROR (Misal Email Gagal), BATALKAN SEMUA
            DB::rollBack();
            
            // Kembalikan pesan error asli agar kita tahu kenapa email gagal
            return response()->json(['message' => 'Gagal Register: ' . $e->getMessage()], 500);
        }
    }

    // ---------------------------------
    // FUNGSI UNTUK LOGIN USER
    // ---------------------------------
    public function login(Request $request)
        {
            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json(['message' => 'Email atau Password salah'], 401);
            }

            $user = User::where('email', $request['email'])->firstOrFail();

            // --- TAMBAHKAN CEK VERIFIKASI ---
            if (!$user->hasVerifiedEmail()) {
                return response()->json(['message' => 'Email belum diverifikasi. Cek inbox Anda.'], 403);
            }
            // --------------------------------

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login berhasil',
                'user' => $user,
                'token' => $token,
            ]);
        }
    public function googleLogin(Request $request)
        {
            // Validasi
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            // 1. Cek apakah user sudah ada?
            $user = User::where('email', $request->email)->first();

            if ($user) {
                // --- SKENARIO A: USER LAMA (LOGIN LANGSUNG) ---
                $token = $user->createToken('google_auth_token')->plainTextToken;

                return response()->json([
                    'status' => 'exists', // Penanda untuk Flutter
                    'message' => 'Login berhasil',
                    'user' => $user,
                    'token' => $token,
                ]);
            } else {
                // --- SKENARIO B: USER BARU (MINTA PASSWORD) ---
                // Kita TIDAK membuat user di sini. Kita suruh Flutter buka form baru.
                return response()->json([
                    'status' => 'new_user', // Penanda untuk Flutter
                    'message' => 'Email belum terdaftar, silakan registrasi.',
                    'email' => $request->email,
                    'name' => $request->name // Kembalikan nama dari Google untuk pre-fill
                ]);
            }
        }   
        
    // --- FUNGSI BARU: VERIFIKASI EMAIL ---
    public function verifyEmail(Request $request, $id)
    {
        // 1. Cari user berdasarkan ID yang ada di URL
        $user = User::findOrFail($id);

        // 2. Cek apakah tanda tangan digital (Signature) URL valid?
        // Agar tidak ada orang iseng nembak URL sembarangan
        if (!$request->hasValidSignature()) {
            return response()->json(['message' => 'Link verifikasi tidak valid atau sudah kadaluarsa.'], 403);
        }

        // 3. Jika user sudah verifikasi sebelumnya
        if ($user->hasVerifiedEmail()) {
             return response()->json(['message' => 'Email sudah diverifikasi sebelumnya. Silakan login.']);
        }

        // 4. Verifikasi Email User
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // 5. Tampilkan pesan sukses (Ini akan muncul di Browser HP user)
        return response()->json(['message' => 'Email BERHASIL diverifikasi! Silakan kembali ke aplikasi dan Login.']);
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

    // 1. GET PROFILE
    public function profile(Request $request)
    {
        $user = $request->user();

        // Siapkan URL foto lengkap agar bisa diakses Flutter
        $photoUrl = $user->profile_photo_path 
            ? asset('storage/' . $user->profile_photo_path) 
            : null;

        return response()->json([
            'message' => 'Profil berhasil diambil',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'bio' => $user->bio, // Kolom baru
                'photo_url' => $photoUrl, // Kirim URL lengkap
            ]
        ], 200);
    }

    // 2. UPDATE PROFILE
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        // Validasi
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'bio'  => 'nullable|string|max:500',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // Max 2MB
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        // Update Text
        $user->name = $request->name;
        $user->bio = $request->bio;

        // Update Foto (Jika ada upload baru)
        if ($request->hasFile('photo')) {
            // Hapus foto lama jika ada (opsional, biar hemat storage)
            if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }

            // Simpan foto baru
            $path = $request->file('photo')->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
        }

        $user->save();

        // Response Data Baru
        $photoUrl = $user->profile_photo_path 
            ? asset('storage/' . $user->profile_photo_path) 
            : null;

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => [
                'name' => $user->name,
                'bio' => $user->bio,
                'photo_url' => $photoUrl,
            ]
        ], 200);
    }
}
