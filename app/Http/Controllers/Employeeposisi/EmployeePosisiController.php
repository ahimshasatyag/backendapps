<?php

namespace App\Http\Controllers\Employeeposisi;

use App\Http\Controllers\Controller;
use App\Models\Employeeposisi\EmployeePosisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EmployeePosisiController extends Controller
{
    public function index(Request $request)
    {
        $data = EmployeePosisi::all();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function show($id)
    {
        $data = EmployeePosisi::find($id);

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee Posisi not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nm_karyawan_posisi' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $id_karyawan_posisi = $request->id_karyawan_posisi;
            
            if (!$id_karyawan_posisi) {
                $maxId = EmployeePosisi::max('id_karyawan_posisi');
                $id_karyawan_posisi = $maxId ? ((int)$maxId + 1) : 1;
            }

            $posisi = EmployeePosisi::create([
                'id_karyawan_posisi' => $id_karyawan_posisi,
                'nm_karyawan_posisi' => $request->nm_karyawan_posisi,
                'date_create' => now()
            ]);

            Log::info('Simpan Data Karyawan Posisi Kode : ' . $posisi->id_karyawan_posisi);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'kode' => $posisi->id_karyawan_posisi,
                'message' => 'Employee Posisi created successfully',
                'data' => $posisi
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $posisi = EmployeePosisi::find($id);

        if (!$posisi) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee Posisi not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nm_karyawan_posisi' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $posisi->update([
                'nm_karyawan_posisi' => $request->nm_karyawan_posisi,
                'date_update' => now()
            ]);

            Log::info('Update Data Karyawan Posisi Kode : ' . $posisi->id_karyawan_posisi);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'kode' => $posisi->id_karyawan_posisi,
                'message' => 'Employee Posisi updated successfully',
                'data' => $posisi
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal update data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $posisi = EmployeePosisi::find($id);

        if (!$posisi) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee Posisi not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            $posisi->delete();

            Log::info('Hapus Data Karyawan Posisi Kode : ' . $posisi->id_karyawan_posisi);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Employee Posisi deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal hapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}


