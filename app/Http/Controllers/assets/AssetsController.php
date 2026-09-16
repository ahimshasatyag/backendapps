<?php

namespace App\Http\Controllers\assets;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\assets\Assets;

class AssetsController extends Controller
{
    /**
     * Menampilkan data list Asset (mirip fungsi data/index pada Cform)
     */
    public function index(Request $request)
    {
        $perPage = $request->per_page ?? 10;
        $search = $request->search;
        
        $data = Assets::getList($perPage, $search);
        
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Mendapatkan data pendukung untuk form tambah/edit (mirip yang dipanggil di tambah() / edit())
     */
    public function supportData()
    {
        return response()->json([
            'status' => true,
            'data_asset' => Assets::getAssets(),
            'data_assets_type' => Assets::getTypes(),
            'data_assets_category' => Assets::getCategories(),
        ]);
    }

    /**
     * Menampilkan detail Asset untuk halaman edit
     */
    public function show($id)
    {
        $data = Assets::getAssetById($id);
        if (!$data) {
            return response()->json(['status' => false, 'message' => 'Data not found'], 404);
        }
        
        $data_sn = Assets::getSerialNumbers($id);
        
        return response()->json([
            'status' => true,
            'data' => $data,
            'data_sn' => $data_sn
        ]);
    }

    /**
     * Proses simpan data (mirip fungsi simpan pada Cform)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'inventory_type_id' => 'required',
            'inventory_category_id' => 'required',
            'procured_date' => 'required',
            'purchased_date' => 'required',
            'status' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $name = $request->name;
            $inventory_type_id = $request->inventory_type_id;
            $inventory_category_id = $request->inventory_category_id;
            $procured_date = date("Y-m-d", strtotime($request->procured_date));
            $purchased_date = date("Y-m-d", strtotime($request->purchased_date));
            $deskripsi = $request->deskripsi;
            $serial = $request->serial;
            $status = $request->status;
            $f_print = $request->f_print;

            $asset_id = DB::table('inventory_assets')->insertGetId([
                'name' => $name,
                'inventory_type_id' => $inventory_type_id,
                'inventory_category_id' => $inventory_category_id,
                'procured_date' => $procured_date,
                'purchased_date' => $purchased_date,
                'deskripsi' => $deskripsi,
                'date_create' => now(),
                'serial' => $serial,
                'status' => $status,
                'f_print' => $f_print,
            ]);

            // Diharapkan berupa array of object: [{name_sn: 'x', serial_number: 'y'}]
            $sns = $request->sn; 
            if ($sns && is_array($sns)) {
                foreach ($sns as $sn) {
                    if (!empty($sn['name_sn']) && !empty($sn['serial_number'])) {
                        DB::table('inventory_serial_number')->insert([
                            'asset_id' => $asset_id,
                            'name_sn' => $sn['name_sn'],
                            'serial_number' => $sn['serial_number'],
                            'date_create' => now()
                        ]);
                    }
                }
            }

            // Jika kategori adalah "Mobil" atau "Motor", tambahkan jadwal perpanjangan STNK
            $category = DB::table('inventory_category')->where('id', $inventory_category_id)->first();
            if ($category && in_array($category->name, ['Mobil', 'Motor'])) {
                $schedule_id = DB::table('inventory_schedule')->insertGetId([
                    'asset_id' => $asset_id,
                    'name' => 'Perpanjang STNK',
                    'deskripsi' => 'Perpanjangan STNK',
                    'periode' => 'Yearly',
                    'due_date' => $purchased_date,
                    'reminder' => '3,7',
                    'date_create' => now()
                ]);

                DB::table('inventory_schedule_pic')->insert([
                    'inventory_schedule_id' => $schedule_id,
                    'username' => 'admin',
                    'date_create' => now()
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil disimpan',
                'kode' => $name,
                'id' => $asset_id
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Proses update data (mirip fungsi update pada Cform)
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'inventory_type_id' => 'required',
            'inventory_category_id' => 'required',
            'procured_date' => 'required',
            'purchased_date' => 'required',
            'status' => 'required',
        ]);

        DB::beginTransaction();
        try {
            DB::table('inventory_assets')->where('id', $id)->update([
                'name' => $request->name,
                'inventory_type_id' => $request->inventory_type_id,
                'inventory_category_id' => $request->inventory_category_id,
                'procured_date' => date("Y-m-d", strtotime($request->procured_date)),
                'purchased_date' => date("Y-m-d", strtotime($request->purchased_date)),
                'deskripsi' => $request->deskripsi,
                'date_update' => now(),
                'serial' => $request->serial,
                'status' => $request->status,
                'f_print' => $request->f_print,
            ]);

            // Hapus serial sebelumnya
            DB::table('inventory_serial_number')->where('asset_id', $id)->delete();

            // Insert ulang serial baru
            $sns = $request->sn;
            if ($sns && is_array($sns)) {
                foreach ($sns as $sn) {
                    if (!empty($sn['name_sn']) && !empty($sn['serial_number'])) {
                        DB::table('inventory_serial_number')->insert([
                            'asset_id' => $id,
                            'name_sn' => $sn['name_sn'],
                            'serial_number' => $sn['serial_number'],
                            'date_create' => now()
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diupdate',
                'kode' => $request->name
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal: ' . $e->getMessage()
            ], 500);
        }
    }
}
