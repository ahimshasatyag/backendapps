<?php

namespace App\Http\Controllers\Counter;

use App\Http\Controllers\Controller;
use App\Models\Counter\Counter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CounterController extends Controller
{
    public function index(Request $request)
    {
        $counters = Counter::all();

        return response()->json([
            'status' => 'success',
            'data' => $counters
        ]);
    }

    public function show($id_counter, $periode)
    {
        $counter = Counter::where('id_counter', $id_counter)
            ->where('periode', $periode)
            ->first();

        if (!$counter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Counter not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $counter
        ]);
    }

    public function update(Request $request, $id_counter, $periode)
    {
        $counter = Counter::where('id_counter', $id_counter)
            ->where('periode', $periode)
            ->first();

        if (!$counter) {
            return response()->json([
                'status' => 'error',
                'message' => 'Counter not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'no_counter' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        // Using query builder to safely update composite keys
        Counter::where('id_counter', $id_counter)
            ->where('periode', $periode)
            ->update([
                'no_counter' => $request->no_counter
            ]);

        // Re-fetch to get updated model
        $updatedCounter = Counter::where('id_counter', $id_counter)
            ->where('periode', $periode)
            ->first();

        Log::info('Update Data Counter Kode : ' . $id_counter . ' Periode : ' . $periode);

        return response()->json([
            'status' => 'success',
            'message' => 'Counter updated successfully',
            'data' => $updatedCounter
        ]);
    }
}
