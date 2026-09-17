<?php

namespace App\Http\Controllers\employeedivisi;

use App\Http\Controllers\Controller;
use App\Models\Employeedivisi\EmployeeDivisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EmployeedivisiController extends Controller
{
    /**
     * Menampilkan daftar data 
     */
    public function index()
    {
        $data = EmployeeDivisi::all();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * Menampilkan data spesifik 
     */
    public function show($id)
    {
        $data = EmployeeDivisi::find($id);

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    /**
     * Menyimpan data baru 
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nm_karyawan_divisi' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $divisi = new EmployeeDivisi();
        $divisi->id_karyawan_divisi = EmployeeDivisi::max('id_karyawan_divisi') + 1;


        $divisi->nm_karyawan_divisi = $request->nm_karyawan_divisi;
        $divisi->save();

        Log::info('Simpan Data Employee Divisi Kode : ' . $divisi->id_karyawan_divisi);

        return response()->json([
            'status' => 'success',
            'message' => 'Data created successfully',
            'data' => $divisi
        ], 201);
    }

    /**
     * Update data 
     */
    public function update(Request $request, $id)
    {
        $divisi = EmployeeDivisi::find($id);

        if (!$divisi) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nm_karyawan_divisi' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $divisi->nm_karyawan_divisi = $request->nm_karyawan_divisi;
        $divisi->save();

        Log::info('Update Data Employee Divisi Kode : ' . $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Data updated successfully',
            'data' => $divisi
        ]);
    }

    /**
     * Hapus data 
     */
    public function destroy($id)
    {
        $divisi = EmployeeDivisi::find($id);

        if (!$divisi) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found'
            ], 404);
        }

        // Soft delete 
        $divisi->delete();

        Log::info('Hapus Data Employee Divisi Kode : ' . $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Data deleted successfully'
        ]);
    }
}


