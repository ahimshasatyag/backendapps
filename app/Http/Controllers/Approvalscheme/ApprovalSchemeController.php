<?php

namespace App\Http\Controllers\Approvalscheme;

use App\Http\Controllers\Controller;
use App\Models\Approvalscheme\ApprovalScheme;
use App\Models\Approvalscheme\SchemeRule;
use App\Models\Approvalitems\ApprovalRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ApprovalSchemeController extends Controller
{
    public function index(Request $request)
    {
        $schemes = ApprovalScheme::all();

        return response()->json([
            'status' => 'success',
            'data' => $schemes
        ]);
    }

    public function supportData()
    {
        $rules = ApprovalRule::where('is_active', 1)->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'rules' => $rules
            ]
        ]);
    }

    public function show($id)
    {
        $scheme = ApprovalScheme::where('id', $id)->first();

        if (!$scheme) {
            return response()->json([
                'status' => 'error',
                'message' => 'Approval Scheme not found'
            ], 404);
        }

        $rules = SchemeRule::where('scheme_id', $id)->pluck('rule_id')->toArray();

        return response()->json([
            'status' => 'success',
            'data' => [
                'scheme' => $scheme,
                'selected_rule_ids' => $rules
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'scheme_name' => 'required|string',
            'description' => 'required|string',
            'rule_ids' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $scheme = ApprovalScheme::create([
                'scheme_name' => $request->scheme_name,
                'description' => $request->description
            ]);

            if ($request->has('rule_ids') && is_array($request->rule_ids)) {
                foreach ($request->rule_ids as $rule_id) {
                    SchemeRule::create([
                        'scheme_id' => $scheme->id,
                        'rule_id' => $rule_id
                    ]);
                }
            }

            DB::commit();
            
            Log::info('Simpan Data Approval Scheme Kode : ' . $scheme->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Approval Scheme created successfully',
                'data' => $scheme
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Approval Scheme: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $scheme = ApprovalScheme::find($id);

        if (!$scheme) {
            return response()->json([
                'status' => 'error',
                'message' => 'Approval Scheme not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'scheme_name' => 'required|string',
            'description' => 'required|string',
            'rule_ids' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $scheme->update([
                'scheme_name' => $request->scheme_name,
                'description' => $request->description
            ]);

            SchemeRule::where('scheme_id', $id)->delete();

            if ($request->has('rule_ids') && is_array($request->rule_ids)) {
                foreach ($request->rule_ids as $rule_id) {
                    SchemeRule::create([
                        'scheme_id' => $id,
                        'rule_id' => $rule_id
                    ]);
                }
            }

            DB::commit();
            
            Log::info('Update Data Approval Scheme Kode : ' . $id);

            return response()->json([
                'status' => 'success',
                'message' => 'Approval Scheme updated successfully',
                'data' => $scheme
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Approval Scheme: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $scheme = ApprovalScheme::find($id);

        if (!$scheme) {
            return response()->json([
                'status' => 'error',
                'message' => 'Approval Scheme not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            SchemeRule::where('scheme_id', $id)->delete();
            $scheme->delete();

            DB::commit();

            Log::info('Delete Data Approval Scheme Kode : ' . $id);

            return response()->json([
                'status' => 'success',
                'message' => 'Approval Scheme deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete Approval Scheme: ' . $e->getMessage()
            ], 500);
        }
    }
}
