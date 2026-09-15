<?php

namespace App\Http\Controllers\salesretur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\salesretur\Salesretur;

class SalesreturController extends Controller
{
    /**
     * Get list of Sales Retur
     */
    public function index(Request $request)
    {
        $query = DB::table('tb_retur_penjualan_hdr as a')
            ->leftJoin('m_customers as b', 'a.id_customers', '=', 'b.id_customers')
            ->select(
                'a.id',
                'a.code as code_sr',
                'a.date_retur as date',
                'b.nm_customers',
                'a.status_retur as status'
            );

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $terms = explode(',', $search);
                foreach ($terms as $term) {
                    $term = trim($term);
                    $q->orWhere('a.code', 'like', "%$term%")
                        ->orWhere('b.nm_customers', 'like', "%$term%")
                        ->orWhere('a.status_retur', 'like', "%$term%");
                }
            });
        }

        $returs = $query->orderBy('a.id', 'desc')->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'data' => $returs
        ]);
    }

    /**
     * Support data
     */
    public function supportData(Request $request)
    {
        $customers = DB::table('m_customers')->get();

        return response()->json([
            'status' => true,
            'customers' => $customers,
        ]);
    }

    public function getDo(Request $request)
    {
        $id_customer = $request->id_customer;
        $data = DB::table('tb_do_hdr')
            ->where('status_do', 'DELIVERED')
            ->where('id_customers', $id_customer)
            ->orderBy('id_do', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function getDoDetail(Request $request)
    {
        $id_do = $request->id_do;
        $data = DB::table('tb_do_dtl as a')
            ->leftJoin('m_product as b', 'a.id_product', '=', 'b.id_product')
            ->leftJoin('m_product_sn as c', 'a.nbarcode', '=', 'c.sn')
            ->select('a.id_product', 'b.code_product', 'b.nm_product', 'c.id_product_sn', 'a.nbarcode')
            ->where('a.id_do', $id_do)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function getSo(Request $request)
    {
        $id_customer = $request->id_customer;
        $data = DB::table('tb_so_hdr')
            ->where('status_so', 'SALES ORDER')
            ->where('flag_cancel', '0')
            ->where('id_customers', $id_customer)
            ->orderBy('id_so', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    public function show($id)
    {
        $data_header = DB::table('tb_retur_penjualan_hdr as a')
            ->select('a.*', 'a.code as code_sr', 'a.date_retur as date', 'a.status_retur as status', 'a.id_so as id_do')
            ->where('id', $id)
            ->first();

        if (!$data_header) {
            return response()->json(['status' => false, 'message' => 'Sales Retur not found'], 404);
        }

        $data_barang = DB::table('tb_retur_penjualan_dtl as a')
            ->leftJoin('m_product as b', 'a.id_product', '=', 'b.id_product')
            ->select('a.id_product', 'b.code_product', 'b.nm_product', 'a.nqty')
            ->where('a.retur_penjualan_id', $id)
            ->get();

        $data_so = DB::table('tb_so_hdr')
            ->where('status_so', 'SALES ORDER')
            ->where('id_customers', $data_header->id_customers)
            ->orderBy('id_so', 'desc')
            ->get();

        $customers = DB::table('m_customers')->get();

        return response()->json([
            'status' => true,
            'data' => $data_header,
            'data_barang' => $data_barang,
            'data_do' => $data_so,
            'customers' => $customers
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'id_customers' => 'required',
            'id_do' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $id_customers = $request->id_customers;
            $id_so = $request->id_do;
            $date = $request->date ? date("Y-m-d", strtotime($request->date)) : date("Y-m-d");
            $total_product = $request->total_product;

            $periode = date("Ym", strtotime($date));
            $code_sr = $this->generateRunningNumberSR('SR', $periode);

            $id_retur = DB::table('tb_retur_penjualan_hdr')->insertGetId([
                'code' => $code_sr,
                'id_customers' => $id_customers,
                'id_so' => $id_so,
                'date_retur' => $date,
                'status_retur' => 'DRAFT',
                'total' => 0,
                'sisa' => 0,
                'flag_cancel' => 0,
                'flag_close' => 0
            ]);

            if ($total_product > 0) {
                for ($i = 1; $i <= $total_product; $i++) {
                    $ceklis = $request->input('ceklis_' . $i);
                    if ($ceklis === 'on' || $ceklis === true || $ceklis == 1) {
                        $id_product = $request->input('id_product_' . $i);
                        $nqty = $request->input('nqty_' . $i) ?? 1;

                        DB::table('tb_retur_penjualan_dtl')->insert([
                            'retur_penjualan_id' => $id_retur,
                            'id_product' => $id_product,
                            'nqty' => $nqty,
                            'product_price' => 0
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => true, 'kode' => $id_retur, 'code_sr' => $code_sr, 'message' => 'Sales Retur saved successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'id_customers' => 'required',
            'id_do' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $id_retur = $id;
            $id_customers = $request->id_customers;
            $id_so = $request->id_do;
            $date = $request->date ? date("Y-m-d", strtotime($request->date)) : date("Y-m-d");
            $total_product = $request->total_product;

            DB::table('tb_retur_penjualan_hdr')
                ->where('id', $id_retur)
                ->update([
                    'id_customers' => $id_customers,
                    'id_so' => $id_so,
                    'date_retur' => $date
                ]);

            // Delete existing details
            DB::table('tb_retur_penjualan_dtl')->where('retur_penjualan_id', $id_retur)->delete();

            // Insert new details
            if ($total_product > 0) {
                for ($i = 1; $i <= $total_product; $i++) {
                    $ceklis = $request->input('ceklis_' . $i);
                    if ($ceklis === 'on' || $ceklis === true || $ceklis == 1) {
                        $id_product = $request->input('id_product_' . $i);
                        $nqty = $request->input('nqty_' . $i) ?? 1;

                        DB::table('tb_retur_penjualan_dtl')->insert([
                            'retur_penjualan_id' => $id_retur,
                            'id_product' => $id_product,
                            'nqty' => $nqty,
                            'product_price' => 0
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => true, 'kode' => $id_retur, 'message' => 'Sales Retur updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    public function confirm(Request $request, $id)
    {
        DB::table('tb_retur_penjualan_hdr')
            ->where('id', $id)
            ->update(['status_retur' => 'CONFIRMED']);

        return response()->json(['status' => true, 'message' => 'Sales Retur confirmed']);
    }

    public function cancel(Request $request, $id)
    {
        DB::table('tb_retur_penjualan_hdr')
            ->where('id', $id)
            ->update(['status_retur' => 'CANCEL', 'flag_cancel' => 1]);

        return response()->json(['status' => true, 'message' => 'Sales Retur cancelled']);
    }

    private function generateRunningNumberSR($prefix, $periode)
    {
        $prefix_full = $prefix . '-' . $periode . '-';
        $last_data = DB::table('tb_retur_penjualan_hdr')
            ->where('code', 'like', $prefix_full . '%')
            ->orderBy('code', 'desc')
            ->first();

        if ($last_data) {
            $last_number = intval(substr($last_data->code, -4));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }

        return $prefix_full . str_pad($new_number, 4, '0', STR_PAD_LEFT);
    }
}

