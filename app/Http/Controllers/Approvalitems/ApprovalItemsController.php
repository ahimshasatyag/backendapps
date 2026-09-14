<?php

namespace App\Http\Controllers\Approvalitems;

use App\Http\Controllers\Controller;
use App\Models\Approvalitems\ApprovalRule;
use App\Models\Approvalitems\ApprovalApprover;
use App\Models\Approvalscheme\ApprovalScheme;
use App\Models\Approvalscheme\SchemeRule;
use App\Models\Userlevel\UserLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ApprovalItemsController extends Controller
{
    public function index(Request $request)
    {
        $rules = ApprovalRule::all();

        return response()->json([
            'status' => 'success',
            'data' => $rules
        ]);
    }

    public function supportData()
    {
        $schemes = ApprovalScheme::where('is_active', 1)->get();
        $levels = UserLevel::all();

        return response()->json([
            'status' => 'success',
            'data' => [
                'schemes' => $schemes,
                'levels' => $levels
            ]
        ]);
    }

    public function show($id)
    {
        $rule = ApprovalRule::where('id', $id)->first();

        if (!$rule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Approval Rule not found'
            ], 404);
        }

        $schemeRule = SchemeRule::where('rule_id', $id)->first();
        $selected_scheme_id = $schemeRule ? $schemeRule->scheme_id : '';

        $approvers = ApprovalApprover::where('approval_rule_id', $id)->pluck('level_id')->toArray();

        return response()->json([
            'status' => 'success',
            'data' => [
                'rule' => $rule,
                'selected_scheme_id' => $selected_scheme_id,
                'selected_level_ids' => $approvers
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'approval_name' => 'required|string',
            'description' => 'required|string',
            'approval_type' => 'required|string',
            'module_name' => 'required|string',
            'table_name' => 'required|string',
            'status_column_name' => 'nullable|string',
            'new_status_approve' => 'nullable|string',
            'new_status_reject' => 'nullable|string',
            'rule' => 'required|string',
            'level_ids' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $rule = ApprovalRule::create([
                'approval_name' => $request->approval_name,
                'description' => $request->description,
                'approval_type' => $request->approval_type,
                'module_name' => $request->module_name,
                'table_name' => $request->table_name,
                'status_column_name' => $request->status_column_name,
                'new_status_approve' => $request->new_status_approve,
                'new_status_reject' => $request->new_status_reject,
                'rule' => $request->rule
            ]);

            if ($request->has('level_ids') && is_array($request->level_ids)) {
                foreach ($request->level_ids as $level_id) {
                    ApprovalApprover::create([
                        'approval_rule_id' => $rule->id,
                        'level_id' => $level_id
                    ]);
                }
            }

            DB::commit();
            
            Log::info('Simpan Data Approval Items Kode : ' . $rule->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Approval Rule created successfully',
                'data' => $rule
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Approval Rule: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $rule = ApprovalRule::find($id);

        if (!$rule) {
            return response()->json([
                'status' => 'error',
                'message' => 'Approval Rule not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'approval_name' => 'required|string',
            'description' => 'required|string',
            'approval_type' => 'required|string',
            'module_name' => 'required|string',
            'table_name' => 'required|string',
            'status_column_name' => 'nullable|string',
            'new_status_approve' => 'nullable|string',
            'new_status_reject' => 'nullable|string',
            'rule' => 'required|string',
            'level_ids' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $rule->update([
                'approval_name' => $request->approval_name,
                'description' => $request->description,
                'approval_type' => $request->approval_type,
                'module_name' => $request->module_name,
                'table_name' => $request->table_name,
                'status_column_name' => $request->status_column_name,
                'new_status_approve' => $request->new_status_approve,
                'new_status_reject' => $request->new_status_reject,
                'rule' => $request->rule
            ]);

            ApprovalApprover::where('approval_rule_id', $id)->delete();

            if ($request->has('level_ids') && is_array($request->level_ids)) {
                foreach ($request->level_ids as $level_id) {
                    ApprovalApprover::create([
                        'approval_rule_id' => $id,
                        'level_id' => $level_id
                    ]);
                }
            }

            DB::commit();
            
            Log::info('Update Data Approval Items Kode : ' . $id);

            return response()->json([
                'status' => 'success',
                'message' => 'Approval Rule updated successfully',
                'data' => $rule
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Approval Rule: ' . $e->getMessage()
            ], 500);
        }
    }
}
