<?php

namespace App\Http\Controllers\Inventorytype;

use App\Http\Controllers\Controller;
use App\Models\Inventorytype\InventoryType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class InventoryTypeController extends Controller
{
    public function index(Request $request)
    {
        $inventories = InventoryType::all();

        return response()->json([
            'status' => 'success',
            'data' => $inventories
        ]);
    }

    public function show($id)
    {
        $inventory = InventoryType::find($id);

        if (!$inventory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory Type not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $inventory
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

        $inventory = InventoryType::create([
            'name' => $request->name
        ]);

        Log::info('Simpan Data Inventory Type Kode : ' . $inventory->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Inventory Type created successfully',
            'data' => $inventory
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $inventory = InventoryType::find($id);

        if (!$inventory) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventory Type not found'
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

        $inventory->update([
            'name' => $request->name
        ]);

        Log::info('Update Data Inventory Type Kode : ' . $inventory->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Inventory Type updated successfully',
            'data' => $inventory
        ]);
    }
}
