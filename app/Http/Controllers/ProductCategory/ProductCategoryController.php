<?php

namespace App\Http\Controllers\ProductCategory;

use App\Http\Controllers\Controller;
use App\Models\Product\ProductKategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class ProductCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = ProductKategori::get();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    public function show($id)
    {
        $category = ProductKategori::find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Category not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $category
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nm_product_kategori' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $category = new ProductKategori();
        $category->nm_product_kategori = $request->nm_product_kategori;
        // The id_product_kategori is auto-incrementing in Laravel's Eloquent unless specified otherwise.
        $category->save();

        // Simulate the CI behavior of setting kode_product_kategori based on the generated ID
        $kode_product_kategori = str_pad($category->id_product_kategori, 3, '0', STR_PAD_LEFT);
        $category->kode_product_kategori = $kode_product_kategori;
        $category->save();

        Log::info('Simpan Data Product Category Kode : ' . $category->id_product_kategori);

        return response()->json([
            'status' => 'success',
            'message' => 'Product Category created successfully',
            'data' => $category
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $category = ProductKategori::find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Category not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nm_product_kategori' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $category->nm_product_kategori = $request->nm_product_kategori;
        $category->save();

        Log::info('Update Data Product Category Kode : ' . $category->id_product_kategori);

        return response()->json([
            'status' => 'success',
            'message' => 'Product Category updated successfully',
            'data' => $category
        ]);
    }

    public function destroy($id)
    {
        $category = ProductKategori::find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Category not found'
            ], 404);
        }
        
        // Hard delete since there is no soft delete flag in the table
        $category->delete();

        Log::info('Hapus Data Product Category Kode : ' . $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Product Category deleted successfully'
        ]);
    }
}
