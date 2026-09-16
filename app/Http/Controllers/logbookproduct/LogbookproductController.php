<?php

namespace App\Http\Controllers\logbookproduct;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\logbookproduct\Logbookproduct;
use Carbon\Carbon;

class LogbookproductController extends Controller
{
    /**
     * Menampilkan data 
     */
    public function index(Request $request)
    {
        $data = Logbookproduct::getData();
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Data Master untuk tambah 
     */
    public function create(Request $request)
    {
        $data_barang = Logbookproduct::getDataBarang();
        $data_type_kerusakan = Logbookproduct::getDataTypeKerusakan();

        return response()->json([
            'status' => true,
            'data_barang' => $data_barang,
            'data_type_kerusakan' => $data_type_kerusakan
        ]);
    }

    /**
     * Simpan data 
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_product' => 'required',
            'id_type_kerusakan' => 'required',
            'date_log_book' => 'required'
        ]);

        try {
            DB::beginTransaction();

            $date_log_book = Carbon::parse($request->date_log_book)->format('Y-m-d');
            $masalah = htmlspecialchars($request->masalah_hidden ?? $request->masalah ?? '');
            $solusi = htmlspecialchars($request->solusi_hidden ?? $request->solusi ?? '');
            $catatan = htmlspecialchars($request->catatan_hidden ?? $request->catatan ?? '');
            $username = $request->header('x-username') ?? 'admin';

            $id_log_book = Logbookproduct::insertData(
                $request->id_product,
                $request->id_type_kerusakan,
                $date_log_book,
                $masalah,
                $solusi,
                $catatan,
                $username
            );

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil disimpan',
                'kode' => $id_log_book
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
     * Data untuk edit 
     */
    public function show($id_log_book)
    {
        $data = Logbookproduct::getDataHeader($id_log_book);
        $data_barang = Logbookproduct::getDataBarang();
        $data_type_kerusakan = Logbookproduct::getDataTypeKerusakan();
        
        return response()->json([
            'status' => true,
            'data' => $data,
            'data_barang' => $data_barang,
            'data_type_kerusakan' => $data_type_kerusakan
        ]);
    }

    /**
     * Update data 
     */
    public function update(Request $request)
    {
        $request->validate([
            'id_log_book' => 'required',
            'id_product' => 'required',
            'id_type_kerusakan' => 'required',
            'date_log_book' => 'required'
        ]);

        try {
            DB::beginTransaction();

            $id_log_book = $request->id_log_book;
            $date_log_book = Carbon::parse($request->date_log_book)->format('Y-m-d');
            $masalah = htmlspecialchars($request->masalah_hidden ?? $request->masalah ?? '');
            $solusi = htmlspecialchars($request->solusi_hidden ?? $request->solusi ?? '');
            $catatan = htmlspecialchars($request->catatan_hidden ?? $request->catatan ?? '');
            $username = $request->header('x-username') ?? 'admin';

            Logbookproduct::updateData(
                $id_log_book,
                $request->id_product,
                $request->id_type_kerusakan,
                $date_log_book,
                $masalah,
                $solusi,
                $catatan,
                $username
            );

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diupdate',
                'kode' => $id_log_book
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
     * Hapus data 
     */
    public function destroy(Request $request)
    {
        $request->validate([
            'id_log_book' => 'required'
        ]);

        try {
            DB::beginTransaction();
            Logbookproduct::deleteData($request->id_log_book);
            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil dihapus'
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
     * Upload Gambar CKEditor
     */
    public function uploadGambar(Request $request)
    {
        if ($request->hasFile('upload')) {
            $file = $request->file('upload');
            $extension = $file->getClientOriginalExtension();
            $allowed_extension = ["jpg", "gif", "png"];
            
            if (in_array(strtolower($extension), $allowed_extension)) {
                $new_image_name = time() . rand() . '.' . $extension;
                $file->move(public_path('assets/images/upload/'), $new_image_name);
                
                $function_number = $request->query('CKEditorFuncNum');
                $url = asset('assets/images/upload/' . $new_image_name);
                $message = '';
                
                return response("<script type='text/javascript'>window.parent.CKEDITOR.tools.callFunction($function_number, '$url', '$message');</script>")
                    ->header('Content-Type', 'text/html');
            }
        }
        
        return response('Error', 400);
    }
}
