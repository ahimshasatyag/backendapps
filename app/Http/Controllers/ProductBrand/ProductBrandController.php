<?php

namespace App\Http\Controllers\ProductBrand;

use App\Http\Controllers\Controller;
use App\Models\Product\ProductBrand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProductBrandController extends Controller
{
    public function index(Request $request)
    {
        $brands = ProductBrand::get();

        return response()->json([
            'status' => 'success',
            'data' => $brands
        ]);
    }

    public function show($id)
    {
        $brand = ProductBrand::find($id);

        if (!$brand) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Brand not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $brand
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_product_brand' => 'required|string|unique:m_product_brand,id_product_brand',
            'nm_product_brand' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $brand = new ProductBrand();
        $brand->id_product_brand = strtoupper($request->id_product_brand);
        $brand->nm_product_brand = $request->nm_product_brand;
        $brand->save();

        Log::info('Simpan Data Product Brand Kode : ' . $brand->id_product_brand);

        return response()->json([
            'status' => 'success',
            'message' => 'Product Brand created successfully',
            'data' => $brand
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $brand = ProductBrand::find($id);

        if (!$brand) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Brand not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nm_product_brand' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $brand->nm_product_brand = $request->nm_product_brand;
        $brand->save();

        Log::info('Update Data Product Brand Kode : ' . $brand->id_product_brand);

        return response()->json([
            'status' => 'success',
            'message' => 'Product Brand updated successfully',
            'data' => $brand
        ]);
    }

    public function destroy($id)
    {
        $brand = ProductBrand::find($id);

        if (!$brand) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Brand not found'
            ], 404);
        }
        
        // Hard delete to match your latest controller standard
        $brand->delete();

        Log::info('Hapus Data Product Brand Kode : ' . $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Product Brand deleted successfully'
        ]);
    }
}
