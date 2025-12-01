<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use App\Models\User;
use App\Models\Category;

class AuthController extends Controller
{
    // --- HELPER: BUAT KATEGORI DEFAULT ---
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

    // --- 1. REGISTER ---
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

        DB::beginTransaction();

        try {
            // Buat User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            // Buat Kategori Default
            $this->createDefaultCategories($user->id);

            // Kirim Email Verifikasi
            event(new Registered($user));

            DB::commit();

            return response()->json(['message' => 'Registrasi berhasil. Silakan cek email untuk verifikasi.'], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal Register: ' . $e->getMessage()], 500);
        }
    }

    // --- 2. LOGIN ---
    public function login(Request $request)
    {
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Email atau Password salah'], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();

        // Cek Verifikasi Email
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

    // --- 3. GOOGLE LOGIN ---
    public function googleLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'name'  => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Cek User Existing
        $user = User::where('email', $request->email)->first();

        if ($user) {
            // Auto Verify jika belum
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

        // User Baru via Google
        $newUser = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make(uniqid()), // Password random
            'email_verified_at' => now(),       // Langsung verified
        ]);

        // Buat Kategori Default
        $this->createDefaultCategories($newUser->id);

        $token = $newUser->createToken('google_auth_token')->plainTextToken;

        return response()->json([
            'status' => 'created',
            'message' => 'Akun Google berhasil dibuat.',
            'user' => $newUser,
            'token' => $token,
        ]);
    }

    // --- 4. VERIFIKASI EMAIL (KLIK DARI EMAIL) ---
    public function verifyEmail(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (!$request->hasValidSignature()) {
            // Jika link kadaluarsa/tidak valid
            return response('Link verifikasi tidak valid atau sudah kadaluarsa.', 403);
        }

        if (!$user->hasVerifiedEmail()) {
            if ($user->markEmailAsVerified()) {
                event(new Verified($user));
            }
        }

        // Tampilkan halaman HTML cantik
        return view('email.email-verification');
    }

    // --- 5. LOGOUT ---
    public function logout(Request $request)
    {
        $user = $request->user();
        if ($user) {
            $user->currentAccessToken()->delete();
        }

        return response()->json(['message' => 'Berhasil logout'], 200);
    }

    // --- 6. GET PROFILE ---
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

    // --- 7. UPDATE PROFILE ---
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
            // Hapus foto lama jika ada
            if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
                Storage::disk('public')->delete($user->profile_photo_path);
            }
            // Simpan foto baru
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

    // --- 8. GANTI PASSWORD (FITUR BARU) ---
    public function changePassword(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validasi gagal', 'errors' => $validator->errors()], 422);
        }

        // Cek apakah password lama benar
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Password lama salah'], 400);
        }

        // Update password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password berhasil diubah'], 200);
    }
}