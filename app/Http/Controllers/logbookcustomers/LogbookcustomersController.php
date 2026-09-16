<?php

namespace App\Http\Controllers\logbookcustomers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\logbookcustomers\Logbookcustomers;
use Carbon\Carbon;

class LogbookcustomersController extends Controller
{
    /**
     * Menampilkan data 
     */
    public function index(Request $request)
    {
        $data = Logbookcustomers::getData();
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
        $data_customers = Logbookcustomers::getDataCustomers();

        return response()->json([
            'status' => true,
            'data_customers' => $data_customers
        ]);
    }

    /**
     * Simpan data 
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_customers' => 'required',
            'date_log_book' => 'required'
        ]);

        try {
            DB::beginTransaction();

            $date_log_book = Carbon::parse($request->date_log_book)->format('Y-m-d');
            $masalah = htmlspecialchars($request->masalah_hidden ?? $request->masalah ?? '');
            $solusi = htmlspecialchars($request->solusi_hidden ?? $request->solusi ?? '');
            $catatan = htmlspecialchars($request->catatan_hidden ?? $request->catatan ?? '');
            $username = $request->header('x-username') ?? 'admin';

            $id_log_book = Logbookcustomers::insertData(
                $request->id_customers,
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
        $data = Logbookcustomers::getDataHeader($id_log_book);
        $data_customers = Logbookcustomers::getDataCustomers();
        
        return response()->json([
            'status' => true,
            'data' => $data,
            'data_customers' => $data_customers
        ]);
    }

    /**
     * Update data
     */
    public function update(Request $request)
    {
        $request->validate([
            'id_log_book' => 'required',
            'id_customers' => 'required',
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

            Logbookcustomers::updateData(
                $id_log_book,
                $request->id_customers,
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
            Logbookcustomers::deleteData($request->id_log_book);
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
