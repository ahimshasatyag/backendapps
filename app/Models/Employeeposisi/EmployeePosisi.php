<?php

namespace App\Models\Employeeposisi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeePosisi extends Model
{
    use HasFactory;

    protected $table = 'm_karyawan_posisi';
    protected $primaryKey = 'id_karyawan_posisi';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';
}
