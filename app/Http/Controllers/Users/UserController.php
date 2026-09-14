<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\Users\User;
use App\Models\Users\UserLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    // Equivalent to data() in Mmaster.php / Cform.php
    public function index()
    {
        // Get all users with their level
        $users = User::with('level')->get()->map(function ($user) {
            return [
                'username' => $user->username,
                'nm_users' => $user->nm_users,
                'nm_users_level' => $user->level ? $user->level->nm_users_level : null,
                'is_active' => $user->is_active == 1 ? 'Aktif' : 'Tidak Aktif',
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $users
        ], 200);
    }

    // Equivalent to data_level()
    public function levels()
    {
        $levels = UserLevel::all();
        return response()->json([
            'status' => 'success',
            'data' => $levels
        ], 200);
    }

    // Equivalent to simpan()
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|unique:m_users,username',
            'password' => 'required|string',
            'nm_users' => 'required|string',
            'id_users_level' => 'required',
            'is_active' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $username = strtolower($request->input('username'));
            
            $user = User::create([
                'username' => $username,
                'password' => Hash::make($request->input('password')),
                'nm_users' => $request->input('nm_users'),
                'id_users_level' => $request->input('id_users_level'),
                'is_active' => $request->input('is_active'),
            ]);

            Log::info('Simpan Data User Kode : ' . $username);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil disimpan',
                'kode' => $username,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    // Equivalent to edit() & data_header()
    public function show($username)
    {
        $user = User::where('username', $username)->first();
        
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $user
        ], 200);
    }

    // Equivalent to update()
    public function update(Request $request, $username)
    {
        $request->validate([
            'nm_users' => 'required|string',
            'id_users_level' => 'required',
            'is_active' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $user = User::where('username', $username)->first();
            
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User tidak ditemukan'
                ], 404);
            }

            $updateData = [
                'nm_users' => $request->input('nm_users'),
                'id_users_level' => $request->input('id_users_level'),
                'is_active' => $request->input('is_active'),
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->input('password'));
            }

            $user->update($updateData);

            Log::info('Update Data User Kode : ' . $username);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil diupdate',
                'kode' => $username,
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupdate data: ' . $e->getMessage()
            ], 500);
        }
    }
}
