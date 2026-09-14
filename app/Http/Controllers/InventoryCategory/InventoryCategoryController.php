<?php

namespace App\Http\Controllers\InventoryCategory;

use App\Http\Controllers\Controller;
use App\Models\InventoryCategory\InventoryCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class InventoryCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = InventoryCategory::all();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ]);
    }

    public function show($id)
    {
        $category = InventoryCategory::find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory Category not found'
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
            'name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $category = InventoryCategory::create([
            'name' => $request->name
        ]);

        Log::info('Simpan Data Inventory Category Kode : ' . $category->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Inventory Category created successfully',
            'data' => $category
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $category = InventoryCategory::find($id);

        if (!$category) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory Category not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $category->update([
            'name' => $request->name
        ]);

        Log::info('Update Data Inventory Category Kode : ' . $category->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Inventory Category updated successfully',
            'data' => $category
        ]);
    }
}
