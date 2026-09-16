<?php

namespace App\Http\Controllers\sop;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\sop\Sop;
use Illuminate\Support\Str;

class SopController extends Controller
{
    /**
     * Menampilkan data 
     */
    public function index(Request $request)
    {
        $data = Sop::getData();
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Menampilkan data divisi 
     */
    public function dataDivisi(Request $request)
    {
        $username = $request->header('x-username') ?? 'admin';
        $data = Sop::getDataDivisi($username);
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Menampilkan list sop by divisi 
     */
    public function listSop(Request $request, $divisi)
    {
        $data = Sop::bacaSemua($divisi);
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Menyimpan data baru 
     */
    public function store(Request $request)
    {
        $request->validate([
            'divisi' => 'required',
            'code_sop' => 'required',
            'nm_sop' => 'required',
            'file_pdf' => 'nullable|mimes:pdf'
        ]);

        try {
            DB::beginTransaction();
            $username = $request->header('x-username') ?? 'admin';
            
            $file_pdf = null;
            if ($request->hasFile('file_pdf')) {
                $file = $request->file('file_pdf');
                $file_pdf = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('assets/upload'), $file_pdf);
            }

            $id_sop = Sop::insertData(
                $request->divisi,
                $request->code_sop,
                $request->nm_sop,
                $file_pdf,
                $username
            );

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil disimpan',
                'kode' => $id_sop
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail untuk diedit
     */
    public function show($id_sop)
    {
        $data = Sop::getDataHeader($id_sop);
        $data_history = Sop::getDataHistory($id_sop);
        
        return response()->json([
            'status' => true,
            'data' => $data,
            'data_history' => $data_history
        ]);
    }

    /**
     * Update data 
     */
    public function update(Request $request)
    {
        $request->validate([
            'id_sop' => 'required',
            'code_sop' => 'required',
            'nm_sop' => 'required',
            'file_pdf' => 'nullable|mimes:pdf'
        ]);

        try {
            DB::beginTransaction();
            $username = $request->header('x-username') ?? 'admin';
            
            $file_pdf = null;
            if ($request->hasFile('file_pdf')) {
                $file = $request->file('file_pdf');
                $file_pdf = Str::random(20) . '.' . $file->getClientOriginalExtension();
                $file->move(public_path('assets/upload'), $file_pdf);
            }

            $f_revisi = $request->f_revisi;
            $status = null;

            if ($f_revisi == 't') {
                Sop::insertHistory($request->id_sop, $username);
                $status = 'IN PROGRESS';
            }

            Sop::updateData(
                $request->id_sop,
                $request->code_sop,
                $request->nm_sop,
                $file_pdf,
                $status,
                $username
            );

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diupdate',
                'kode' => $request->id_sop
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Confirm SOP 
     */
    public function confirm(Request $request)
    {
        $id_sop = $request->id_sop;
        $status_sop = 'FINALIZE';

        Sop::updateStatus($id_sop, $status_sop);

        return response()->json([
            'status' => true,
            'message' => 'SOP berhasil Confirm',
            'type_message' => 'success',
            'header_message' => 'Confirm !'
        ]);
    }
}
