<?php

namespace App\Http\Controllers\ProductUnit;

use App\Http\Controllers\Controller;
use App\Models\Product\ProductSatuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProductUnitController extends Controller
{
    public function index(Request $request)
    {
        $satuan = ProductSatuan::get();

        return response()->json([
            'status' => 'success',
            'data' => $satuan
        ]);
    }

    public function show($id)
    {
        $satuan = ProductSatuan::find($id);

        if (!$satuan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Unit not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $satuan
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nm_product_satuan' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $satuan = new ProductSatuan();
        $satuan->nm_product_satuan = $request->nm_product_satuan;
        // id_product_satuan is expected to be auto-incrementing or handled by DB sequence/trigger
        $satuan->save();

        Log::info('Simpan Data Product Unit Kode : ' . $satuan->id_product_satuan);

        return response()->json([
            'status' => 'success',
            'message' => 'Product Unit created successfully',
            'data' => $satuan
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $satuan = ProductSatuan::find($id);

        if (!$satuan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Unit not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nm_product_satuan' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $satuan->nm_product_satuan = $request->nm_product_satuan;
        $satuan->save();

        Log::info('Update Data Product Unit Kode : ' . $satuan->id_product_satuan);

        return response()->json([
            'status' => 'success',
            'message' => 'Product Unit updated successfully',
            'data' => $satuan
        ]);
    }

    public function destroy($id)
    {
        $satuan = ProductSatuan::find($id);

        if (!$satuan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Unit not found'
            ], 404);
        }
        
        // Hard delete equivalent to match recent ProductCategory logic
        $satuan->delete();

        Log::info('Hapus Data Product Unit Kode : ' . $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Product Unit deleted successfully'
        ]);
    }
}
