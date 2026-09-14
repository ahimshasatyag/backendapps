<?php

namespace App\Http\Controllers\Setting;

use App\Http\Controllers\Controller;
use App\Models\Setting\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $settings = Setting::select('*', 'id as setting_id')->get();

        return response()->json([
            'status' => 'success',
            'data' => $settings
        ]);
    }

    public function show($id)
    {
        $setting = Setting::select('*', 'id as setting_id')->where('id', $id)->first();

        if (!$setting) {
            return response()->json([
                'status' => 'error',
                'message' => 'Setting not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $setting
        ]);
    }

    public function update(Request $request, $id)
    {
        $setting = Setting::find($id);

        if (!$setting) {
            return response()->json([
                'status' => 'error',
                'message' => 'Setting not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'setting_label' => 'sometimes|string',
            'setting_key' => 'sometimes|string',
            'setting_value' => 'sometimes|string',
            'setting_flag' => 'sometimes|string',
            'setting_note' => 'sometimes|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $setting->update($request->only([
            'setting_label',
            'setting_key',
            'setting_value',
            'setting_flag',
            'setting_note'
        ]));

        Log::info('Update Data Setting Kode : ' . $id);

        $updatedSetting = Setting::select('*', 'id as setting_id')->where('id', $id)->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Setting updated successfully',
            'data' => $updatedSetting
        ]);
    }
}
