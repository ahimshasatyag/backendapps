<?php

namespace App\Models\Employee;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Employeedivisi\EmployeeDivisi;
use App\Models\Employeeposisi\EmployeePosisi;

class Employee extends Model
{
    use HasFactory;

    protected $table = 'm_karyawan';
    protected $primaryKey = 'id_karyawan';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $fillable = [
        'nm_karyawan',
        'tempat_lahir',
        'id_karyawan_divisi',
        'date_lahir',
        'id_karyawan_posisi',
        'no_hp',
        'jenis_kelamin',
        'karyawan_address',
        'karyawan_email',
        'flag_agent',
        'flag_status',
        'flag_sales'
    ];

    public function divisi()
    {
        return $this->belongsTo(EmployeeDivisi::class, 'id_karyawan_divisi', 'id_karyawan_divisi');
    }

    public function posisi()
    {
        return $this->belongsTo(EmployeePosisi::class, 'id_karyawan_posisi', 'id_karyawan_posisi');
    }
}
