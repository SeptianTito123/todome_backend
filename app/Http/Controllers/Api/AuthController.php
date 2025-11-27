<?php

namespace App\Http\Controllers\Api;

use Illuminate\Auth\Events\Registered;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Category; 
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    // Helper function untuk membuat kategori default
    private function createDefaultCategories($userId)
    {
        $defaultCategories = ['Kuliah', 'Kerja', 'Daily'];
        foreach ($defaultCategories as $catName) {
            Category::create([
                'user_id' => $userId,
                'name' => $catName
            ]);
        }
    }

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

            // 2. [FIX] Buat Kategori Default
            $this->createDefaultCategories($user->id);

            // 3. Kirim Email
            event(new Registered($user));

            // 4. Jika sampai sini aman, Simpan Permanen
            DB::commit();

            return response()->json(['message' => 'Registrasi berhasil. Silakan cek email untuk verifikasi.'], 201);

        } catch (\Exception $e) {
            // 5. JIKA ADA ERROR, BATALKAN SEMUA
            DB::rollBack();
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

        // --- CEK VERIFIKASI ---
        if (!$user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Email belum diverifikasi. Cek inbox Anda.'], 403);
        }

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
            'name'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Cek apakah user sudah ada
        $user = User::where('email', $request->email)->first();

        if ($user) {
            // Jika belum verified -> kita tandai verified otomatis
            if (!$user->hasVerifiedEmail()) {
                $user->email_verified_at = now();
                $user->save();
            }

            $token = $user->createToken('google_auth_token')->plainTextToken;

            return response()->json([
                'status' => 'exists',
                'message' => 'Login berhasil',
                'user' => $user,
                'token' => $token,
            ]);
        }

        // --- USER BARU (GOOGLE) -> AUTO REGISTER + VERIFIED ---
        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make(uniqid()), // password random
            'email_verified_at' => now(),       // langsung verified
        ]);

        // [FIX] Buat Kategori Default untuk user Google baru
        $this->createDefaultCategories($newUser->id);

        $token = $newUser->createToken('google_auth_token')->plainTextToken;

        return response()->json([
            'status' => 'created',
            'message' => 'Akun Google berhasil dibuat dan login otomatis.',
            'user' => $newUser,
            'token' => $token,
        ]);
    }

    // --- VERIFIKASI EMAIL ---
    public function verifyEmail(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (!$request->hasValidSignature()) {
            return response()->json(['message' => 'Link verifikasi tidak valid atau sudah kadaluarsa.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
             return response()->json(['message' => 'Email sudah diverifikasi sebelumnya. Silakan login.']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => 'Email BERHASIL diverifikasi! Silakan kembali ke aplikasi dan Login.']);
    }

    // --- LOGOUT ---
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Berhasil logout'
        ], 200);
    }

    // 1. GET PROFILE
    public function profile(Request $request)
    {
        $user = $request->user();

        $photoUrl = $user->profile_photo_path 
            ? asset('storage/' . $user->profile_photo_path) 
            : null;

        return response()->json([
            'message' => 'Profil berhasil diambil',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'bio' => $user->bio,
                'photo_url' => $photoUrl,
            ]
        ], 200);
    }

    // 2. UPDATE PROFILE
    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'bio'  => 'nullable|string|max:500',
            'photo' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        $user->name = $request->name;
        $user->bio = $request->bio;

        if ($request->hasFile('photo')) {
            if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            $path = $request->file('photo')->store('profile-photos', 'public');
            $user->profile_photo_path = $path;
        }

        $user->save();

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