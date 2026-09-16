<?php

namespace App\Http\Controllers\inventoryschedule;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\inventoryschedule\Inventoryschedule;

class InventoryscheduleController extends Controller
{
    /**
     * 
     */
    public function index(Request $request)
    {
        $perPage = $request->per_page ?? 10;
        $search = $request->search;
        
        $data = Inventoryschedule::getList($perPage, $search);
        
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Mendapatkan data pendukung untuk form tambah/edit
     */
    public function supportData()
    {
        return response()->json([
            'status' => true,
            'data_asset' => Inventoryschedule::getAssets(),
            'data_user' => Inventoryschedule::getUsers(),
        ]);
    }

    /**
     * Menampilkan detail Schedule untuk halaman edit
     */
    public function show($id)
    {
        $data = Inventoryschedule::getDetail($id);
        if (!$data) {
            return response()->json(['status' => false, 'message' => 'Data not found'], 404);
        }
        
        $data_pic = Inventoryschedule::getSchedulePic($id);
        
        return response()->json([
            'status' => true,
            'data' => $data,
            'data_pic' => $data_pic
        ]);
    }

    /**
     * Proses simpan data 
     */
    public function store(Request $request)
    {
        $request->validate([
            'asset_id' => 'required',
            'name' => 'required',
            'deskripsi' => 'required',
            'periode' => 'required',
            'due_date' => 'required',
            'reminder' => 'required|array',
            'username' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $asset_id = $request->asset_id;
            $name = $request->name;
            $deskripsi = $request->deskripsi;
            $periode = $request->periode;
            $due_date = date("Y-m-d", strtotime($request->due_date));
            $reminder_values = implode(",", $request->reminder);

            $id = DB::table('inventory_schedule')->insertGetId([
                'asset_id' => $asset_id,
                'name' => $name,
                'deskripsi' => $deskripsi,
                'periode' => $periode,
                'due_date' => $due_date,
                'reminder' => $reminder_values,
                'date_create' => now()
            ]);

            $usernames = $request->username;
            if ($usernames && is_array($usernames)) {
                foreach ($usernames as $val) {
                    DB::table('inventory_schedule_pic')->insert([
                        'inventory_schedule_id' => $id,
                        'username' => $val,
                        'date_create' => now()
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil disimpan',
                'kode' => $id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proses update data 
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'asset_id' => 'required',
            'name' => 'required',
            'deskripsi' => 'required',
            'periode' => 'required',
            'due_date' => 'required',
            'reminder' => 'required|array',
            'username' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $asset_id = $request->asset_id;
            $name = $request->name;
            $deskripsi = $request->deskripsi;
            $periode = $request->periode;
            $due_date = date("Y-m-d", strtotime($request->due_date));
            $reminder_values = implode(",", $request->reminder);

            DB::table('inventory_schedule')->where('id', $id)->update([
                'asset_id' => $asset_id,
                'name' => $name,
                'deskripsi' => $deskripsi,
                'periode' => $periode,
                'due_date' => $due_date,
                'reminder' => $reminder_values,
                'date_update' => now()
            ]);

            // Delete existing PIC
            DB::table('inventory_schedule_pic')->where('inventory_schedule_id', $id)->delete();

            // Insert updated PIC
            $usernames = $request->username;
            if ($usernames && is_array($usernames)) {
                foreach ($usernames as $val) {
                    DB::table('inventory_schedule_pic')->insert([
                        'inventory_schedule_id' => $id,
                        'username' => $val,
                        'date_create' => now()
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diupdate',
                'kode' => $id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal update data: ' . $e->getMessage()
            ], 500);
        }
    }
}
