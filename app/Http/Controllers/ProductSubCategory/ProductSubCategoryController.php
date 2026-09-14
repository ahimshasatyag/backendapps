<?php

namespace App\Http\Controllers\ProductSubCategory;

use App\Http\Controllers\Controller;
use App\Models\Product\ProductSubKategori;
use App\Models\Product\ProductKategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProductSubCategoryController extends Controller
{
    public function index(Request $request)
    {
        $subcategories = ProductSubKategori::leftJoin('m_product_kategori', 'm_product_sub_kategori.id_product_kategori', '=', 'm_product_kategori.id_product_kategori')
            ->select('m_product_sub_kategori.*', 'm_product_kategori.nm_product_kategori')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $subcategories
        ]);
    }

    public function supportData()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'data_kategori' => ProductKategori::all(),
            ]
        ]);
    }

    public function show($id)
    {
        $subcategory = ProductSubKategori::leftJoin('m_product_kategori', 'm_product_sub_kategori.id_product_kategori', '=', 'm_product_kategori.id_product_kategori')
            ->select('m_product_sub_kategori.*', 'm_product_kategori.nm_product_kategori')
            ->where('m_product_sub_kategori.id_product_sub_kategori', $id)
            ->first();

        if (!$subcategory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Sub Category not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $subcategory
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_product_kategori' => 'required',
            'nm_product_sub_kategori' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $subcategory = new ProductSubKategori();
        $subcategory->id_product_kategori = $request->id_product_kategori;
        $subcategory->nm_product_sub_kategori = $request->nm_product_sub_kategori;
        // The id_product_sub_kategori is auto-incrementing in Laravel's Eloquent unless specified otherwise.
        $subcategory->save();

        // Simulate the CI behavior of setting kode_product_sub_kategori based on the generated ID
        $kode_product_sub_kategori = str_pad($subcategory->id_product_sub_kategori, 3, '0', STR_PAD_LEFT);
        $subcategory->kode_product_sub_kategori = $kode_product_sub_kategori;
        $subcategory->save();

        Log::info('Simpan Data Product Sub Category Kode : ' . $subcategory->id_product_sub_kategori);

        return response()->json([
            'status' => 'success',
            'message' => 'Product Sub Category created successfully',
            'data' => $subcategory
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $subcategory = ProductSubKategori::find($id);

        if (!$subcategory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Sub Category not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_product_kategori' => 'required',
            'nm_product_sub_kategori' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $subcategory->id_product_kategori = $request->id_product_kategori;
        $subcategory->nm_product_sub_kategori = $request->nm_product_sub_kategori;
        $subcategory->save();

        Log::info('Update Data Product Sub Category Kode : ' . $subcategory->id_product_sub_kategori);

        return response()->json([
            'status' => 'success',
            'message' => 'Product Sub Category updated successfully',
            'data' => $subcategory
        ]);
    }

    public function destroy($id)
    {
        $subcategory = ProductSubKategori::find($id);

        if (!$subcategory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Sub Category not found'
            ], 404);
        }
        
        // Hard delete since there is no soft delete flag in the table according to your recent modification
        $subcategory->delete();

        Log::info('Hapus Data Product Sub Category Kode : ' . $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Product Sub Category deleted successfully'
        ]);
    }
}
