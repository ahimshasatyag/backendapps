<?php

namespace App\Http\Controllers\salescontract;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalescontractController extends Controller
{
    /**
     * Display a listing of the resource.
     * Mirip dengan Cform::data_table() dan Cform::data_table2()
     */
    public function index(Request $request)
    {
        $has_sc = $request->query('has_sc');

        $query = DB::table('tb_so_hdr as a')
            ->leftJoin('m_customers as b', 'a.id_customers', '=', 'b.id_customers')
            ->leftJoin('m_karyawan as c', 'a.id_karyawan', '=', 'c.id_karyawan')
            ->whereIn('a.status_so', ['SALE TO INVOICE', 'SALES ORDER']);

        if ($has_sc === 'true') {
            $query->join('tb_sales_contract_hdr as d', 'a.id_sales_contract', '=', 'd.id_sales_contract')
                  ->select('a.id_so', 'a.id_sales_contract', 'd.code_sales_contract', 'a.date_so', 'a.code_so', 'b.nm_customers');
        } else if ($has_sc === 'false') {
            $query->whereNull('a.id_sales_contract')
                  ->select('a.id_so', 'a.id_sales_contract', 'a.date_so', 'a.code_so', 'b.nm_customers');
        } else {
            $query->leftJoin('tb_sales_contract_hdr as d', 'a.id_sales_contract', '=', 'd.id_sales_contract')
                  ->select('a.id_so', 'a.id_sales_contract', 'd.code_sales_contract', 'a.date_so', 'a.code_so', 'b.nm_customers');
        }

        if ($request->has('search')) {
            $search = strtoupper($request->search);
            $query->where(function($q) use ($search) {
                $q->where(DB::raw('UPPER(a.code_so)'), 'like', "%$search%")
                  ->orWhere(DB::raw('UPPER(b.nm_customers)'), 'like', "%$search%");
                
                $q->orWhere(DB::raw('UPPER(d.code_sales_contract)'), 'like', "%$search%");
            });
        }

        $data = $query->orderBy('a.date_so', 'desc')->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Get data header and detail SO for create SC
     * Mirip dengan Mmaster::data_header_so() dan Mmaster::data_detail_so()
     */
    public function createData($id_so)
    {
        $header = DB::table('tb_so_hdr as a')
            ->leftJoin('m_customers as b', 'a.id_customers', '=', 'b.id_customers')
            ->leftJoin('m_karyawan as c', 'a.id_karyawan', '=', 'c.id_karyawan')
            ->select(
                'a.id_so', 'a.date_so', 'a.code_so', 'a.id_customers', 
                'b.nm_customers', 'b.f_company', 'b.customers_address',
                'a.vcurrency', 'b.f_company', 'b.nama_lengkap', 'b.nik', 
                'b.nib', 'b.npwp', 'b.alamat', 'a.ndp_persen', 'a.ntenor'
            )
            ->where('a.id_so', $id_so)
            ->first();

        $detail = DB::table('tb_so_dtl as a')
            ->join('m_product as b', 'a.id_product', '=', 'b.id_product')
            ->select('a.id_so_dtl', 'a.id_so', 'a.id_product', 'b.code_product', 'b.nm_product', 'a.nqty', 'a.product_price', 'a.ntot_product_price_netto')
            ->where('a.id_so', $id_so)
            ->get();

        return response()->json([
            'status' => true,
            'data_header_so' => $header,
            'data_detail_so' => $detail
        ]);
    }

    /**
     * Store new Sales Contract
     * Mirip dengan Cform::simpan()
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_so' => 'required',
            'id_customers' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $id_so = $request->id_so;
            $id_customers = $request->id_customers;
            $date_contract = $request->date_contract;
            
            $periode = date("Ym", strtotime($date_contract));
            $date_contract = date("Y-m-d", strtotime($date_contract));
            
            $code_sales_contract = $this->generateRunningNumber('SC', $periode);

            $f_company_req = $request->f_company;
            $f_company = false;
            $nib = null;
            $npwp = null;
            $nama_lengkap = $request->nm_customers;

            if (!empty($f_company_req)) {
                $f_company = true;
                $nama_lengkap = $request->nama_lengkap;
                $nib = $request->nib;
                $npwp = $request->npwp;
            }

            $nik = $request->nik;
            $alamat = $request->alamat;

            // Insert Header
            $id_sales_contract = DB::table('tb_sales_contract_hdr')->insertGetId([
                'code_sales_contract' => $code_sales_contract,
                'id_customers' => $id_customers,
                'date_contract' => $date_contract,
                'n_amount' => floatval(str_replace(',', '', $request->n_amount ?? 0)),
                'dp_persen' => floatval(str_replace(',', '', $request->dp_persen ?? 0)),
                'dp_nominal' => floatval(str_replace(',', '', $request->dp_nominal ?? 0)),
                'n_sisa' => floatval(str_replace(',', '', $request->n_sisa ?? 0)),
                'lama_cicilan' => floatval(str_replace(',', '', $request->lama_cicilan ?? 0)),
                'jml_cicilan_rp' => floatval(str_replace(',', '', $request->jml_cicilan_rp ?? 0)),
                'f_company' => $f_company,
                'nama_lengkap' => $nama_lengkap,
                'nik' => $nik,
                'nib' => $nib,
                'npwp' => $npwp,
                'alamat' => $alamat
            ]);

            // Update SO Header
            DB::table('tb_so_hdr')->where('id_so', $id_so)->update([
                'id_sales_contract' => $id_sales_contract
            ]);

            // Update Customer
            DB::table('m_customers')->where('id_customers', $id_customers)->update([
                'f_company' => $f_company,
                'nama_lengkap' => $nama_lengkap,
                'nik' => $nik,
                'nib' => $nib,
                'npwp' => $npwp,
                'alamat' => $alamat
            ]);

            // Insert Details
            $products = $request->products ?? [];
            foreach ($products as $item) {
                if (isset($item['id_product']) && !empty($item['pilih_product'])) {
                    DB::table('tb_sales_contract_product')->insert([
                        'id_sales_contract' => $id_sales_contract,
                        'id_product' => $item['id_product'],
                        'product_price' => floatval(str_replace(',', '', $item['ntot_product_price_netto'] ?? 0)),
                        'n_qty' => floatval(str_replace(',', '', $item['n_qty'] ?? 0))
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil disimpan',
                'kode' => $code_sales_contract,
                'id_sales_contract' => $id_sales_contract
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     * Mirip dengan Mmaster::data_header_sc() dan Mmaster::data_detail_sc()
     */
    public function show($id_sales_contract)
    {
        $header = DB::table('tb_so_hdr as a')
            ->leftJoin('m_customers as b', 'a.id_customers', '=', 'b.id_customers')
            ->leftJoin('m_karyawan as c', 'a.id_karyawan', '=', 'c.id_karyawan')
            ->join('tb_sales_contract_hdr as d', 'a.id_sales_contract', '=', 'd.id_sales_contract')
            ->select(
                'a.id_so', 'a.date_so', 'a.code_so', 'a.id_customers', 
                'b.nm_customers', 'b.f_company', 'b.customers_address',
                'a.vcurrency', 'a.id_sales_contract', 'd.code_sales_contract',
                'd.f_company', 'd.date_contract', 'd.n_amount', 'd.dp_persen',
                'd.dp_nominal', 'd.n_sisa', 'd.lama_cicilan', 'd.jml_cicilan_rp',
                'd.nama_lengkap', 'd.nik', 'd.nib', 'd.npwp', 'd.alamat'
            )
            ->where('a.id_sales_contract', $id_sales_contract)
            ->first();

        $detail = DB::table('tb_sales_contract_product as a')
            ->join('m_product as b', 'a.id_product', '=', 'b.id_product')
            ->select('a.id_sales_contract', 'a.id_product', 'b.code_product', 'b.nm_product', 'a.n_qty', 'a.product_price')
            ->where('a.id_sales_contract', $id_sales_contract)
            ->get();

        return response()->json([
            'status' => true,
            'data_header_sc' => $header,
            'data_detail_sc' => $detail
        ]);
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, $id_sales_contract)
    {
        $request->validate([
            'id_customers' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $id_customers = $request->id_customers;
            $date_contract = $request->date_contract ? date("Y-m-d", strtotime($request->date_contract)) : null;
            
            $f_company_req = $request->f_company;
            $f_company = false;
            $nib = null;
            $npwp = null;
            $nama_lengkap = $request->nm_customers;

            if (!empty($f_company_req)) {
                $f_company = true;
                $nama_lengkap = $request->nama_lengkap;
                $nib = $request->nib;
                $npwp = $request->npwp;
            }

            $nik = $request->nik;
            $alamat = $request->alamat;

            // Update Header
            DB::table('tb_sales_contract_hdr')->where('id_sales_contract', $id_sales_contract)->update([
                'id_customers' => $id_customers,
                'date_contract' => $date_contract,
                'n_amount' => floatval(str_replace(',', '', $request->n_amount ?? 0)),
                'dp_persen' => floatval(str_replace(',', '', $request->dp_persen ?? 0)),
                'dp_nominal' => floatval(str_replace(',', '', $request->dp_nominal ?? 0)),
                'n_sisa' => floatval(str_replace(',', '', $request->n_sisa ?? 0)),
                'lama_cicilan' => floatval(str_replace(',', '', $request->lama_cicilan ?? 0)),
                'jml_cicilan_rp' => floatval(str_replace(',', '', $request->jml_cicilan_rp ?? 0)),
                'f_company' => $f_company,
                'nama_lengkap' => $nama_lengkap,
                'nik' => $nik,
                'nib' => $nib,
                'npwp' => $npwp,
                'alamat' => $alamat
            ]);

            // Update Customer
            DB::table('m_customers')->where('id_customers', $id_customers)->update([
                'f_company' => $f_company,
                'nama_lengkap' => $nama_lengkap,
                'nik' => $nik,
                'nib' => $nib,
                'npwp' => $npwp,
                'alamat' => $alamat
            ]);

            // Replace Details
            DB::table('tb_sales_contract_product')->where('id_sales_contract', $id_sales_contract)->delete();
            $products = $request->products ?? [];
            foreach ($products as $item) {
                if (isset($item['id_product']) && !empty($item['pilih_product'])) {
                    DB::table('tb_sales_contract_product')->insert([
                        'id_sales_contract' => $id_sales_contract,
                        'id_product' => $item['id_product'],
                        'product_price' => floatval(str_replace(',', '', $item['ntot_product_price_netto'] ?? 0)),
                        'n_qty' => floatval(str_replace(',', '', $item['n_qty'] ?? 0))
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Data berhasil diupdate'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate running number per tahun
     */
    private function generateRunningNumber($prefix, $periode)
    {
        $year = substr($periode, 0, 4);

        $cek = DB::table('m_counter')
            ->where('id_counter', $prefix)
            ->whereRaw('SUBSTRING(periode, 1, 4) = ?', [$year])
            ->first();

        if ($cek) {
            $no = $cek->no_counter + 1;
            DB::table('m_counter')
                ->where('id_counter', $prefix)
                ->whereRaw('SUBSTRING(periode, 1, 4) = ?', [$year])
                ->update(['no_counter' => $no]);
        } else {
            $no = 1;
            DB::table('m_counter')->insert([
                'id_counter' => $prefix,
                'periode'    => $periode,
                'no_counter' => $no,
            ]);
        }

        return $prefix . '-' . $year . '-' . str_pad($no, 5, '0', STR_PAD_LEFT);
    }
}
