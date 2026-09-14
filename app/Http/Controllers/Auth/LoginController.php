<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Auth\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    public function login(Request $request)
    {
        // Validation rules
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $username = strtolower(trim($request->input('username')));
        $password = $request->input('password');

        // Fetch user from DB
        $user = User::where('username', $username)->first();

        if ($user) {
            // Verify Password
            if (Hash::check($password, $user->password) || password_verify($password, $user->password)) {
                
                if ($user->is_active == 1) {
                    
                    // User data for the frontend
                    $data = [
                        'id_user' => $user->id,
                        'username' => $user->username,
                        'nm_users' => $user->nm_users,
                        'id_users_level' => $user->id_users_level,
                        'id_karyawan' => $user->id_karyawan
                    ];
                    
                    Log::info('API Login Success', ['username' => $username]);
                    
                    return response()->json([
                        'user' => $data,
                        'message' => 'Login successful',
                        // You can issue a real Sanctum token here if you use it later:
                        // 'token' => $user->createToken('auth_token')->plainTextToken,
                        'token' => 'dummy-token' 
                    ], 200);

                } else {
                    return response()->json(['message' => 'Username Tidak Aktif!'], 401);
                }
            } else {
                return response()->json(['message' => 'Password Salah!'], 401);
            }
        } else {
            return response()->json(['message' => 'Username Tidak Terdaftar!'], 401);
        }
    }
}
