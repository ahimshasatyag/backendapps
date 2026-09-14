<?php

namespace App\Models\Customerscontact;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomerContact extends Model
{
    use HasFactory;

    protected $table = 'm_customers_contact';
    protected $primaryKey = 'id_customers_contact';
    public $timestamps = false;

    protected $fillable = [
        'id_customers',
        'nm_customers_contact',
        'customers_contact_posisi',
        'customers_contact_phone',
        'customers_contact_mobile',
        'customers_contact_email',
        'customers_contact_address'
    ];
}
