<?php

namespace App\Models\Customers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Customerscontact\CustomerContact;
use App\Models\Province\Provinsi;
use App\Models\City\Kota;

class Customer extends Model
{
    use HasFactory;

    protected $table = 'm_customers';
    protected $primaryKey = 'id_customers';

    const CREATED_AT = 'date_create';
    const UPDATED_AT = 'date_update';

    protected $fillable = [
        'nm_customers',
        'customers_address',
        'customers_phone',
        'customers_mobile',
        'customers_email',
        'customers_fax',
        'code_customers',
        'f_company',
        'nama_lengkap',
        'nik',
        'nib',
        'npwp',
        'alamat',
        'customers_address_invoice',
        'provinsi',
        'kabupaten',
        'is_blacklist'
    ];

    public function contacts()
    {
        return $this->hasMany(CustomerContact::class, 'id_customers', 'id_customers');
    }

    public function getProvinsi()
    {
        return $this->belongsTo(Provinsi::class, 'provinsi', 'id');
    }

    public function getKabupaten()
    {
        return $this->belongsTo(Kota::class, 'kabupaten', 'id');
    }
}
