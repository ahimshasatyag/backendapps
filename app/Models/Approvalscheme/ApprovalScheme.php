<?php

namespace App\Models\Approvalscheme;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalScheme extends Model
{
    use HasFactory;

    protected $table = 'm_approval_schemes';

    protected $fillable = [
        'scheme_name',
        'description',
        'is_active'
    ];
}
