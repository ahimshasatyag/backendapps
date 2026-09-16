<?php

namespace App\Models\approvebaru;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Approvebaru extends Model
{
    /**
     * Get all pending approvals 
     */
    public static function getPendingApprovals()
    {
        return DB::table('m_approvals as a')
            ->select('a.*', 'u.nm_users as requester_name', 'u2.nm_users as approver_name')
            ->leftJoin('m_users as u', 'a.requester_id', '=', 'u.id')
            ->leftJoin('m_users as u2', 'a.approver_id', '=', 'u2.id')
            ->where('a.status', 'Pending')
            ->where('a.f_request_approval', 1)
            ->orderBy('a.created_at', 'desc')
            ->get();
    }

    /**
     * Update approval status 
     */
    public static function updateApprovalStatus($id, $data)
    {
        $update_data = [
            'approver_id' => $data['approver_id'],
            'status' => $data['status'],
            'action' => $data['action'],
            'approve_date' => now(),
            'updated_at' => now(),
            'f_request_approval' => ($data['status'] === 'approved') ? 1 : 0
        ];

        if ($data['status'] === 'approved') {
            $update_data['new_status_approve'] = $data['status'];
        }

        if ($data['status'] === 'rejected') {
            $update_data['new_status_reject'] = $data['status'];
        }

        if (isset($data['rejection_reason'])) {
            $update_data['rejection_reason'] = $data['rejection_reason'];
        }

        return DB::table('m_approvals')->where('id', $id)->update($update_data);
    }

    /**
     * Get approval by ID 
     */
    public static function getApprovalById($id)
    {
        return DB::table('m_approvals as a')
            ->select('a.*', 'u.nm_users as requester_name', 'u2.nm_users as approver_name')
            ->leftJoin('m_users as u', 'a.requester_id', '=', 'u.id')
            ->leftJoin('m_users as u2', 'a.approver_id', '=', 'u2.id')
            ->where('a.id', $id)
            ->first();
    }
}
