<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryAsset;
use App\Models\Inventory\InventorySerialNumber;
use App\Models\Inventory\InventorySchedule;
use App\Models\Inventory\InventorySchedulePic;
use App\Models\InventoryCategory\InventoryCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $assets = InventoryAsset::with(['category', 'type'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $assets
        ]);
    }

    public function show($id)
    {
        $asset = InventoryAsset::with(['category', 'type', 'serialNumbers', 'schedules.pics'])->find($id);

        if (!$asset) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory Asset not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $asset
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'inventory_type_id' => 'required',
            'inventory_category_id' => 'required',
            'procured_date' => 'required|date',
            'purchased_date' => 'required|date',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $asset = new InventoryAsset();
            $asset->name = $request->name;
            $asset->inventory_type_id = $request->inventory_type_id;
            $asset->inventory_category_id = $request->inventory_category_id;
            $asset->procured_date = date("Y-m-d", strtotime($request->procured_date));
            $asset->purchased_date = date("Y-m-d", strtotime($request->purchased_date));
            $asset->deskripsi = $request->deskripsi;
            $asset->serial = $request->serial;
            $asset->status = $request->status;
            $asset->f_print = $request->f_print;
            $asset->save();

            $jml = $request->jml;
            if ($jml > 0) {
                for ($i = 1; $i <= $jml; $i++) {
                    $name_sn = $request->input('name_sn' . $i);
                    $serial_number = $request->input('serial_number' . $i);

                    if ($name_sn && $serial_number) {
                        InventorySerialNumber::create([
                            'asset_id' => $asset->id,
                            'name_sn' => $name_sn,
                            'serial_number' => $serial_number
                        ]);
                    }
                }
            }

            // STNK logic based on category
            $category = InventoryCategory::find($request->inventory_category_id);
            if ($category && in_array($category->name, ['Mobil', 'Motor'])) {
                $schedule = InventorySchedule::create([
                    'asset_id' => $asset->id,
                    'name' => 'Perpanjang STNK',
                    'deskripsi' => 'Perpanjangan STNK',
                    'periode' => 'Yearly',
                    'due_date' => date("Y-m-d", strtotime($request->purchased_date)),
                    'reminder' => '3,7'
                ]);

                InventorySchedulePic::create([
                    'inventory_schedule_id' => $schedule->id,
                    'username' => 'admin'
                ]);
            }

            DB::commit();

            Log::info('Simpan Data Inventory Asset Kode : ' . $asset->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory Asset created successfully',
                'data' => $asset
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Simpan Inventory Asset: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $asset = InventoryAsset::find($id);

        if (!$asset) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory Asset not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'inventory_type_id' => 'required',
            'inventory_category_id' => 'required',
            'procured_date' => 'required|date',
            'purchased_date' => 'required|date',
            'status' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $asset->name = $request->name;
            $asset->inventory_type_id = $request->inventory_type_id;
            $asset->inventory_category_id = $request->inventory_category_id;
            $asset->procured_date = date("Y-m-d", strtotime($request->procured_date));
            $asset->purchased_date = date("Y-m-d", strtotime($request->purchased_date));
            $asset->deskripsi = $request->deskripsi;
            $asset->serial = $request->serial;
            $asset->status = $request->status;
            $asset->f_print = $request->f_print;
            $asset->save();

            // Replace serial numbers logic
            InventorySerialNumber::where('asset_id', $asset->id)->delete();

            $jml = $request->jml;
            if ($jml > 0) {
                for ($i = 1; $i <= $jml; $i++) {
                    $name_sn = $request->input('name_sn' . $i);
                    $serial_number = $request->input('serial_number' . $i);

                    if ($name_sn && $serial_number) {
                        InventorySerialNumber::create([
                            'asset_id' => $asset->id,
                            'name_sn' => $name_sn,
                            'serial_number' => $serial_number
                        ]);
                    }
                }
            }

            DB::commit();

            Log::info('Update Data Inventory Asset Kode : ' . $asset->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory Asset updated successfully',
                'data' => $asset
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Update Inventory Asset: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $asset = InventoryAsset::find($id);

        if (!$asset) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory Asset not found'
            ], 404);
        }

        DB::beginTransaction();

        try {
            // Delete related tables (Cascade simulation, adjust as necessary if real DB has cascade rules)
            InventorySerialNumber::where('asset_id', $asset->id)->delete();
            
            $schedules = InventorySchedule::where('asset_id', $asset->id)->get();
            foreach($schedules as $schedule) {
                InventorySchedulePic::where('inventory_schedule_id', $schedule->id)->delete();
                $schedule->delete();
            }

            $asset->delete();

            DB::commit();

            Log::info('Hapus Data Inventory Asset Kode : ' . $id);

            return response()->json([
                'status' => 'success',
                'message' => 'Inventory Asset deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Delete Inventory Asset: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}
