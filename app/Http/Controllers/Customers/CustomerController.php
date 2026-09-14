<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Models\Customerscontact\CustomerContact;
use App\Models\Province\Provinsi;
use App\Models\City\Kota;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $customers = Customer::select(
            'm_customers.id_customers',
            'm_customers.code_customers',
            'm_customers.nm_customers',
            'm_customers.customers_phone',
            'm_customers.customers_address',
            'm_provinsi.nama as provinsi_nama',
            'm_kota.nama_kabupaten as kabupaten_nama',
            DB::raw('COALESCE(so.jumlah_so, 0) as jumlah_so')
        )
        ->leftJoin('m_provinsi', 'm_customers.provinsi', '=', 'm_provinsi.id')
        ->leftJoin('m_kota', 'm_customers.kabupaten', '=', 'm_kota.id')
        ->leftJoin(DB::raw('(SELECT id_customers, COUNT(id_so) as jumlah_so FROM tb_so_hdr GROUP BY id_customers) as so'), 'm_customers.id_customers', '=', 'so.id_customers')
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $customers
        ]);
    }

    public function supportData()
    {
        $provinsi_list = Provinsi::all();

        return response()->json([
            'status' => 'success',
            'data' => [
                'provinsi_list' => $provinsi_list
            ]
        ]);
    }

    public function getKabupaten(Request $request)
    {
        $kode_provinsi = $request->input('kode_provinsi');
        $kabupaten_list = Kota::where('kode_provinsi', $kode_provinsi)->get();

        return response()->json([
            'status' => 'success',
            'data' => $kabupaten_list
        ]);
    }

    public function show($id)
    {
        $customer = Customer::with('contacts')->find($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        $provinsi_list = Provinsi::all();
        $kabupaten_list = Kota::where('kode_provinsi', $customer->provinsi)->get();

        $so_list = DB::table('tb_so_hdr as a')
            ->select('a.code_so', 'a.date_so', 'b.nm_karyawan', 'a.ntot_price_netto_amount', 'a.status_so')
            ->leftJoin('m_karyawan as b', 'a.id_karyawan', '=', 'b.id_karyawan')
            ->where('a.id_customers', $id)
            ->orderBy('a.date_so', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'customer' => $customer,
                'provinsi_list' => $provinsi_list,
                'kabupaten_list' => $kabupaten_list,
                'so_list' => $so_list
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nm_customers' => 'required|string',
            'customers_address' => 'required|string',
            'customers_address_invoice' => 'required|string',
            'provinsi' => 'required',
            'kabupaten' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $f_company = $request->f_company ? 1 : 0;
            $nama_lengkap = $f_company ? $request->nama_lengkap : $request->nm_customers;

            $customer = Customer::create([
                'nm_customers' => $request->nm_customers,
                'customers_address' => $request->customers_address,
                'customers_address_invoice' => $request->customers_address_invoice,
                'customers_phone' => $request->customers_phone,
                'customers_mobile' => $request->customers_mobile,
                'customers_email' => $request->customers_email,
                'customers_fax' => $request->customers_fax,
                'provinsi' => $request->provinsi,
                'kabupaten' => $request->kabupaten,
                'f_company' => $f_company,
                'nama_lengkap' => $nama_lengkap,
                'nik' => $f_company ? $request->nik : null,
                'nib' => $f_company ? $request->nib : null,
                'npwp' => $f_company ? $request->npwp : null,
                'alamat' => $f_company ? $request->alamat : null,
                'is_blacklist' => $request->is_blacklist ? 1 : 0,
            ]);

            $code_customers = str_pad($customer->id_customers, 5, "0", STR_PAD_LEFT);
            $customer->code_customers = $code_customers;
            $customer->save();

            $jml = $request->input('jml', 0);
            for ($i = 1; $i <= $jml; $i++) {
                if ($request->input('nm_customers_contact' . $i)) {
                    CustomerContact::create([
                        'id_customers' => $customer->id_customers,
                        'nm_customers_contact' => $request->input('nm_customers_contact' . $i),
                        'customers_contact_posisi' => $request->input('customers_contact_posisi' . $i),
                        'customers_contact_phone' => $request->input('customers_contact_phone' . $i),
                        'customers_contact_email' => $request->input('customers_contact_email' . $i),
                    ]);
                }
            }

            if ($request->has('contacts') && is_array($request->contacts)) {
                foreach ($request->contacts as $contact) {
                    CustomerContact::create([
                        'id_customers' => $customer->id_customers,
                        'nm_customers_contact' => $contact['nm_customers_contact'] ?? null,
                        'customers_contact_posisi' => $contact['customers_contact_posisi'] ?? null,
                        'customers_contact_phone' => $contact['customers_contact_phone'] ?? null,
                        'customers_contact_email' => $contact['customers_contact_email'] ?? null,
                    ]);
                }
            }

            DB::commit();
            Log::info('Simpan Data Customer Kode : ' . $customer->id_customers);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer created successfully',
                'data' => $customer
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Customer: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'nm_customers' => 'required|string',
            'customers_address' => 'required|string',
            'customers_address_invoice' => 'required|string',
            'provinsi' => 'required',
            'kabupaten' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $f_company = $request->f_company ? 1 : 0;
            $nama_lengkap = $f_company ? $request->nama_lengkap : $request->nm_customers;

            $customer->update([
                'nm_customers' => $request->nm_customers,
                'customers_address' => $request->customers_address,
                'customers_address_invoice' => $request->customers_address_invoice,
                'customers_phone' => $request->customers_phone,
                'customers_mobile' => $request->customers_mobile,
                'customers_email' => $request->customers_email,
                'customers_fax' => $request->customers_fax,
                'provinsi' => $request->provinsi,
                'kabupaten' => $request->kabupaten,
                'f_company' => $f_company,
                'nama_lengkap' => $nama_lengkap,
                'nik' => $f_company ? $request->nik : null,
                'nib' => $f_company ? $request->nib : null,
                'npwp' => $f_company ? $request->npwp : null,
                'alamat' => $f_company ? $request->alamat : null,
                'is_blacklist' => $request->is_blacklist ? 1 : 0,
            ]);

            CustomerContact::where('id_customers', $id)->delete();

            $jml = $request->input('jml', 0);
            for ($i = 1; $i <= $jml; $i++) {
                if ($request->input('nm_customers_contact' . $i)) {
                    CustomerContact::create([
                        'id_customers' => $id,
                        'nm_customers_contact' => $request->input('nm_customers_contact' . $i),
                        'customers_contact_posisi' => $request->input('customers_contact_posisi' . $i),
                        'customers_contact_phone' => $request->input('customers_contact_phone' . $i),
                        'customers_contact_email' => $request->input('customers_contact_email' . $i),
                    ]);
                }
            }

            if ($request->has('contacts') && is_array($request->contacts)) {
                foreach ($request->contacts as $contact) {
                    CustomerContact::create([
                        'id_customers' => $id,
                        'nm_customers_contact' => $contact['nm_customers_contact'] ?? null,
                        'customers_contact_posisi' => $contact['customers_contact_posisi'] ?? null,
                        'customers_contact_phone' => $contact['customers_contact_phone'] ?? null,
                        'customers_contact_email' => $contact['customers_contact_email'] ?? null,
                    ]);
                }
            }

            DB::commit();
            Log::info('Update Data Customer Kode : ' . $id);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer updated successfully',
                'data' => $customer
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Customer: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $customer = Customer::find($id);

        if (!$customer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Customer not found'
            ], 404);
        }

        DB::beginTransaction();
        try {
            CustomerContact::where('id_customers', $id)->delete();
            $customer->delete();

            DB::commit();

            Log::info('Hapus Data Customer Kode : ' . $id);

            return response()->json([
                'status' => 'success',
                'message' => 'Customer deleted successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete Customer: ' . $e->getMessage()
            ], 500);
        }
    }
}
