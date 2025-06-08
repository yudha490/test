<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Mission;
use App\Models\UserMission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ApiController extends Controller
{
    /**
     * Handle user registration.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'username' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone_number' => 'required|string|max:20',
            'birth_date' => 'required|date',
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create a new user
        //asjaskasjasjakj
        $user = User::create([
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'phone_number' => $request->phone_number,
            'birth_date' => $request->birth_date,
            'points' => 0, // Default points for new users
        ]);

        // Attach all existing missions to the new user
        $missions = Mission::all();
        foreach ($missions as $mission) {
            UserMission::create([
                'user_id' => $user->id,
                'mission_id' => $mission->id,
                'proof' => '', // No proof initially
                'is_completed' => false, // Not completed initially
            ]);
        }

        // Generate a new API token for the user
        $token = $user->createToken('authToken')->plainTextToken;

        // Return success response with user data and token
        return response()->json([
            'message' => 'User registered successfully and missions assigned.',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 201);
    }

    /**
     * Handle user login.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        // Ubah 'email' jadi 'identity'
        $validator = Validator::make($request->all(), [
            'identity' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Cari user berdasarkan username atau email
        $user = \App\Models\User::where('email', $request->identity)
                ->orWhere('username', $request->identity)
                ->first();

        // Jika user tidak ditemukan atau password salah
        if (!$user || !\Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid login credentials.'], 401);
        }

        // Hapus token sebelumnya
        $user->tokens()->delete();

        // Buat token baru
        $token = $user->createToken('authToken')->plainTextToken;

        return response()->json([
            'message' => 'User logged in successfully.',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Handle user logout.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Revoke the current user's API token
        $request->user()->currentAccessToken()->delete();

        // Return success response
        return response()->json(['message' => 'Successfully logged out.']);
    }

     // --- TAMBAHKAN METHOD INI ---

    /**
     * Get the authenticated user's data.
     * Mengambil data user yang sedang login.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserData(Request $request)
    {
        // $request->user() secara otomatis mendapatkan user yang sedang login
        // berdasarkan token yang dikirim di header.
        return response()->json($request->user());
    }

    /**
     * Update the authenticated user's profile.
     * Memperbarui profil user yang sedang login.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user(); // Dapatkan user yang sedang login

        // Validasi data yang masuk dari Flutter
        $validator = Validator::make($request->all(), [
            'username' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes', // Hanya validasi jika field ada di request
                'string',
                'email',
                'max:255',
                // Pastikan email unik, kecuali jika itu email user yang sedang login
                Rule::unique('users')->ignore($user->id),
            ],
            // 'phone_number' => ['sometimes', 'string', 'max:20', 'nullable'],
            // Jika `phone_number` wajib dan tidak boleh null di DB, ganti 'nullable' dengan 'required' atau hapus 'nullable'
            // dan pastikan Flutter selalu mengirimnya.
            'phone_number' => ['sometimes', 'string', 'max:20'], // Jika tidak nullable, hapus 'nullable'
            'birth_date' => ['sometimes', 'date_format:Y-m-d\TH:i:s.u\Z', 'nullable'], // Sesuaikan dengan format dari Flutter (ISO 8601)
        ]);

        if ($validator->fails()) {
            // Mengembalikan error validasi ke Flutter dengan status 422
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Siapkan data untuk di-update, hanya field yang dikirim dari request
        $dataToUpdate = $request->only([
            'username',
            'email',
            'phone_number',
            'birth_date',
            // Tambahkan field lain yang bisa diupdate jika ada (misal: 'profile_picture_url')
        ]);

        // Cek dan konversi birth_date jika ada
        if (isset($dataToUpdate['birth_date'])) {
            // Jika 'birth_date' dari Flutter adalah string ISO 8601 (contoh: 2000-01-01T00:00:00.000000Z),
            // kamu mungkin perlu mengonversinya ke format yang diharapkan oleh database (misal: YYYY-MM-DD).
            // Laravel Eloquent seringkali bisa mengatasinya otomatis jika tipe data di model adalah `datetime`
            // dan di database adalah `DATE` atau `DATETIME`.
            // Jika kamu hanya ingin tanggal (YYYY-MM-DD), gunakan:
            // $dataToUpdate['birth_date'] = \Carbon\Carbon::parse($dataToUpdate['birth_date'])->format('Y-m-d');
            // Atau cukup biarkan jika Laravel/Eloquent dapat mem-parsingnya secara otomatis.
            if ($dataToUpdate['birth_date'] === null) {
                 // Set null jika dikirim null, penting untuk kolom nullable di DB
                 $dataToUpdate['birth_date'] = null;
            }
        }


        // Update data user
        $user->fill($dataToUpdate); // Mengisi model dengan data dari request
        $user->save(); // Menyimpan perubahan ke database

        // Kembalikan data user yang sudah diupdate ke Flutter
        // $user->fresh() untuk memastikan data yang dikembalikan adalah yang terbaru dari DB
        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $user->fresh()
        ], 200); // 200 OK
    }
}

