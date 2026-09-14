<?php

namespace App\Models\Approvalscheme;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchemeRule extends Model
{
    use HasFactory;

    protected $table = 'm_scheme_rules';

    protected $fillable = [
        'scheme_id',
        'rule_id'
    ];
}
