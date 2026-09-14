<?php

namespace App\Models\Approvalitems;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalApprover extends Model
{
    use HasFactory;

    protected $table = 'm_approval_approvers';

    protected $fillable = [
        'approval_rule_id',
        'level_id'
    ];
}
