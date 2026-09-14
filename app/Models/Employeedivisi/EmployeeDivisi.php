<?php

namespace App\Models\Employeedivisi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeDivisi extends Model
{
    use HasFactory;

    protected $table = 'm_karyawan_divisi';
    protected $primaryKey = 'id_karyawan_divisi';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';
}
