<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function show($username)
    {
        try {
            $user = DB::table('m_users')->where('username', $username)->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            $employeeData = null;
            if ($user->id_karyawan) {
                $employee = DB::table('m_karyawan')
                    ->leftJoin('m_karyawan_divisi', 'm_karyawan.id_karyawan_divisi', '=', 'm_karyawan_divisi.id_karyawan_divisi')
                    ->leftJoin('m_karyawan_posisi', 'm_karyawan.id_karyawan_posisi', '=', 'm_karyawan_posisi.id_karyawan_posisi')
                    ->where('m_karyawan.id_karyawan', $user->id_karyawan)
                    ->select(
                        'm_karyawan.*',
                        'm_karyawan_divisi.nm_karyawan_divisi',
                        'm_karyawan_posisi.nm_karyawan_posisi'
                    )
                    ->first();

                if ($employee) {
                    // Format agar sesuai dengan struktur objek yang diharapkan frontend (nested)
                    $employeeData = [
                        'id_karyawan' => $employee->id_karyawan,
                        'nm_karyawan' => $employee->nm_karyawan,
                        'karyawan_email' => $employee->karyawan_email,
                        'no_hp' => $employee->no_hp,
                        'date_create' => $employee->date_create,
                        'divisi' => [
                            'nm_karyawan_divisi' => $employee->nm_karyawan_divisi
                        ],
                        'posisi' => [
                            'nm_karyawan_posisi' => $employee->nm_karyawan_posisi
                        ]
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user,
                    'employee' => $employeeData
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve profile: ' . $e->getMessage()
            ], 500);
        }
    }
}
