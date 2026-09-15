<?php

namespace App\Http\Controllers\suppliers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    /**
     * Get list of Suppliers
     */
    public function index(Request $request)
    {
        $query = DB::table('m_suppliers')
            ->select(
                'id_suppliers',
                'nm_suppliers',
                'suppliers_address'
            );

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $terms = explode(',', $search);
                foreach ($terms as $term) {
                    $term = trim($term);
                    $q->orWhere('id_suppliers', 'like', "%$term%")
                      ->orWhere('nm_suppliers', 'like', "%$term%")
                      ->orWhere('suppliers_address', 'like', "%$term%");
                }
            });
        }

        $suppliers = $query->orderBy('id_suppliers', 'desc')->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'data'   => $suppliers
        ]);
    }

    /**
     * Support data
     */
    public function supportData(Request $request)
    {
        $mata_uangs = DB::table('m_mata_uang')->get();

        return response()->json([
            'status' => true,
            'mata_uangs' => $mata_uangs,
        ]);
    }

    public function show($id)
    {
        $data_header = DB::table('m_suppliers')
            ->where('id_suppliers', $id)
            ->first();

        if (!$data_header) {
            return response()->json(['status' => false, 'message' => 'Supplier not found'], 404);
        }

        $data_item = DB::table('m_suppliers_contact')
            ->where('id_suppliers', $id)
            ->get();

        $mata_uangs = DB::table('m_mata_uang')->get();

        return response()->json([
            'status' => true,
            'data' => $data_header,
            'data_item' => $data_item,
            'mata_uangs' => $mata_uangs
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nm_suppliers' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Generate ID
            $id_suppliers = $this->generateSupplierId();
            
            $nm_suppliers = $request->nm_suppliers;
            $suppliers_mobile = $request->suppliers_mobile;
            $suppliers_email = $request->suppliers_email;
            $suppliers_address = $request->suppliers_address;
            $suppliers_phone = $request->suppliers_phone;
            $suppliers_fax = $request->suppliers_fax;
            $suppliers_website = $request->suppliers_website;
            $mata_uang = $request->mata_uang;

            $suppliers_logo = null;
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('assets/upload'), $filename);
                $suppliers_logo = $filename;
            }

            DB::table('m_suppliers')->insert([
                'id_suppliers' => $id_suppliers,
                'nm_suppliers' => $nm_suppliers,
                'suppliers_mobile' => $suppliers_mobile,
                'suppliers_email' => $suppliers_email,
                'suppliers_address' => $suppliers_address,
                'suppliers_phone' => $suppliers_phone,
                'suppliers_fax' => $suppliers_fax,
                'suppliers_website' => $suppliers_website,
                'suppliers_logo' => $suppliers_logo,
                'date_create' => now(),
                'id_mata_uang' => $mata_uang
            ]);

            $jml = $request->input('jml', 0);
            for ($i = 1; $i <= $jml; $i++) {
                $nm_suppliers_contact = $request->input('nm_suppliers_contact' . $i);
                $suppliers_contact_posisi = $request->input('suppliers_contact_posisi' . $i);
                $suppliers_contact_phone = $request->input('suppliers_contact_phone' . $i);
                $suppliers_contact_email = $request->input('suppliers_contact_email' . $i);

                if ($nm_suppliers_contact) {
                    DB::table('m_suppliers_contact')->insert([
                        'id_suppliers' => $id_suppliers,
                        'nm_suppliers_contact' => $nm_suppliers_contact,
                        'suppliers_contact_posisi' => $suppliers_contact_posisi,
                        'suppliers_contact_phone' => $suppliers_contact_phone,
                        'suppliers_contact_email' => $suppliers_contact_email,
                    ]);
                }
            }

            DB::commit();
            Log::info('Simpan Data Supplier Kode : ' . $id_suppliers);

            return response()->json([
                'status' => true,
                'kode' => $id_suppliers,
                'message' => 'Supplier saved successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nm_suppliers' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $id_suppliers = $id;
            $nm_suppliers = $request->nm_suppliers;
            $suppliers_mobile = $request->suppliers_mobile;
            $suppliers_email = $request->suppliers_email;
            $suppliers_address = $request->suppliers_address;
            $suppliers_phone = $request->suppliers_phone;
            $suppliers_fax = $request->suppliers_fax;
            $suppliers_website = $request->suppliers_website;
            $mata_uang = $request->mata_uang;

            $data = [
                'nm_suppliers' => $nm_suppliers,
                'suppliers_mobile' => $suppliers_mobile,
                'suppliers_email' => $suppliers_email,
                'suppliers_address' => $suppliers_address,
                'suppliers_phone' => $suppliers_phone,
                'suppliers_fax' => $suppliers_fax,
                'suppliers_website' => $suppliers_website,
                'date_update' => now(),
                'id_mata_uang' => $mata_uang
            ];

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('assets/upload'), $filename);
                $data['suppliers_logo'] = $filename;
            }

            DB::table('m_suppliers')
                ->where('id_suppliers', $id_suppliers)
                ->update($data);

            // Delete existing contacts
            DB::table('m_suppliers_contact')->where('id_suppliers', $id_suppliers)->delete();

            // Insert new contacts
            $jml = $request->input('jml', 0);
            for ($i = 1; $i <= $jml; $i++) {
                $nm_suppliers_contact = $request->input('nm_suppliers_contact' . $i);
                $suppliers_contact_posisi = $request->input('suppliers_contact_posisi' . $i);
                $suppliers_contact_phone = $request->input('suppliers_contact_phone' . $i);
                $suppliers_contact_email = $request->input('suppliers_contact_email' . $i);

                if ($nm_suppliers_contact) {
                    DB::table('m_suppliers_contact')->insert([
                        'id_suppliers' => $id_suppliers,
                        'nm_suppliers_contact' => $nm_suppliers_contact,
                        'suppliers_contact_posisi' => $suppliers_contact_posisi,
                        'suppliers_contact_phone' => $suppliers_contact_phone,
                        'suppliers_contact_email' => $suppliers_contact_email,
                    ]);
                }
            }

            DB::commit();
            Log::info('Update Data Supplier Kode : ' . $id_suppliers);

            return response()->json([
                'status' => true,
                'kode' => $id_suppliers,
                'message' => 'Supplier updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function cariSupplier(Request $request)
    {
        $id_suppliers = $request->id_suppliers;
        $data_suppliers = DB::table('m_suppliers')->where('id_suppliers', $id_suppliers)->first();

        return response()->json([
            'status' => true,
            'data' => $data_suppliers
        ]);
    }

    private function generateSupplierId()
    {
        $last_data = DB::table('m_suppliers')
            ->orderBy('id_suppliers', 'desc')
            ->first();
            
        if ($last_data && preg_match('/SUP-(\d+)/', $last_data->id_suppliers, $matches)) {
            $last_number = intval($matches[1]);
            $new_number = $last_number + 1;
        } else {
            // Fallback strategy if format is different or table is empty
            $last_number = DB::table('m_suppliers')->count();
            $new_number = $last_number + 1;
        }
        
        return 'SUP-' . str_pad($new_number, 4, '0', STR_PAD_LEFT);
    }
}
