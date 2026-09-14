<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\Employee\Employee;
use App\Models\Employeedivisi\EmployeeDivisi;
use App\Models\Employeeposisi\EmployeePosisi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $employees = Employee::with(['divisi', 'posisi'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $employees
        ]);
    }

    public function supportData()
    {
        $data_divisi = EmployeeDivisi::all();
        $data_posisi = EmployeePosisi::all();

        return response()->json([
            'status' => 'success',
            'data' => [
                'data_divisi' => $data_divisi,
                'data_posisi' => $data_posisi
            ]
        ]);
    }

    public function show($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $employee
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nm_karyawan' => 'required|string',
            'tempat_lahir' => 'required|string',
            'id_karyawan_divisi' => 'required|integer',
            'date_lahir' => 'required|date',
            'id_karyawan_posisi' => 'required|integer',
            'no_hp' => 'nullable|string',
            'jenis_kelamin' => 'required|string',
            'karyawan_address' => 'nullable|string',
            'karyawan_email' => 'nullable|email',
            'flag_agent' => 'nullable|string',
            'flag_status' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $employee = Employee::create([
            'nm_karyawan' => $request->nm_karyawan,
            'tempat_lahir' => $request->tempat_lahir,
            'id_karyawan_divisi' => $request->id_karyawan_divisi,
            'date_lahir' => date('Y-m-d', strtotime($request->date_lahir)),
            'id_karyawan_posisi' => $request->id_karyawan_posisi,
            'no_hp' => $request->no_hp,
            'jenis_kelamin' => $request->jenis_kelamin,
            'karyawan_address' => $request->karyawan_address,
            'karyawan_email' => $request->karyawan_email,
            'flag_agent' => $request->flag_agent,
            'flag_status' => $request->flag_status,
        ]);

        Log::info('Simpan Data Employee Kode : ' . $employee->id_karyawan);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee created successfully',
            'data' => $employee
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nm_karyawan' => 'required|string',
            'tempat_lahir' => 'required|string',
            'id_karyawan_divisi' => 'required|integer',
            'date_lahir' => 'required|date',
            'id_karyawan_posisi' => 'required|integer',
            'no_hp' => 'nullable|string',
            'jenis_kelamin' => 'required|string',
            'karyawan_address' => 'nullable|string',
            'karyawan_email' => 'nullable|email',
            'flag_agent' => 'nullable|string',
            'flag_status' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $employee->update([
            'nm_karyawan' => $request->nm_karyawan,
            'tempat_lahir' => $request->tempat_lahir,
            'id_karyawan_divisi' => $request->id_karyawan_divisi,
            'date_lahir' => date('Y-m-d', strtotime($request->date_lahir)),
            'id_karyawan_posisi' => $request->id_karyawan_posisi,
            'no_hp' => $request->no_hp,
            'jenis_kelamin' => $request->jenis_kelamin,
            'karyawan_address' => $request->karyawan_address,
            'karyawan_email' => $request->karyawan_email,
            'flag_agent' => $request->flag_agent,
            'flag_status' => $request->flag_status,
        ]);

        Log::info('Update Data Employee Kode : ' . $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee updated successfully',
            'data' => $employee
        ]);
    }

    public function destroy($id)
    {
        $employee = Employee::find($id);

        if (!$employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Employee not found'
            ], 404);
        }

        $employee->delete();

        Log::info('Hapus Data Employee Kode : ' . $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Employee deleted successfully'
        ]);
    }
}
