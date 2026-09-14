<?php

namespace App\Models\Approvalitems;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalRule extends Model
{
    use HasFactory;

    protected $table = 'm_approval_rules';

    protected $fillable = [
        'approval_name',
        'description',
        'approval_type',
        'module_name',
        'table_name',
        'status_column_name',
        'new_status_approve',
        'new_status_reject',
        'rule',
        'is_active'
    ];

    public function approvers()
    {
        return $this->hasMany(ApprovalApprover::class, 'approval_rule_id', 'id');
    }
}
