<?php

namespace App\Http\Controllers\incshipment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IncshipmentController extends Controller
{
    /**
     * Get list of Incoming Shipments
     */
    public function index(Request $request)
    {
        $query = DB::table('tb_incoming_hdr as a')
            ->join('tb_po_hdr as b', 'a.id_po', '=', 'b.id_po')
            ->join('m_suppliers as c', 'a.id_suppliers', '=', 'c.id_suppliers')
            ->select(
                'a.id', 
                'a.code', 
                'c.nm_suppliers', 
                'b.code_po', 
                'a.date_create', 
                'a.status_incoming', 
                'a.f_assign_barcode'
            );

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $terms = explode(',', $search);
                foreach ($terms as $term) {
                    $term = trim($term);
                    $q->orWhere('a.code', 'like', "%$term%")
                      ->orWhere('c.nm_suppliers', 'like', "%$term%")
                      ->orWhere('b.code_po', 'like', "%$term%")
                      ->orWhere('a.status_incoming', 'like', "%$term%");
                }
            });
        }

        $data = $query->orderBy('a.id', 'desc')->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    /**
     * Show Data
     */
    public function show($id)
    {
        $data_header = DB::table('tb_incoming_hdr as a')
            ->join('tb_po_hdr as b', 'a.id_po', '=', 'b.id_po')
            ->join('m_suppliers as c', 'a.id_suppliers', '=', 'c.id_suppliers')
            ->join('m_gudang as d', 'b.id_gudang', '=', 'd.id_gudang')
            ->where('a.id', $id)
            ->select(
                'a.id',
                'a.code',
                'a.id_suppliers',
                'a.id_po',
                'c.nm_suppliers',
                'b.code_po',
                'a.date_receive',
                'a.date_create',
                'a.status_incoming',
                'a.f_assign_barcode',
                'a.f_print_barcode',
                'b.id_gudang',
                'd.nm_gudang',
                'a.f_ok_receive'
            )
            ->first();

        if (!$data_header) {
            return response()->json(['status' => false, 'message' => 'Data not found'], 404);
        }

        $data_detail = DB::table('tb_incoming_dtl as a')
            ->join('m_product as b', 'a.id_product', '=', 'b.id_product')
            ->leftJoin('m_product_satuan as c', 'b.id_product_satuan', '=', 'c.id_product_satuan')
            ->leftJoin('m_product_lokasi as d', 'a.id_product_lokasi_source', '=', 'd.id_product_lokasi')
            ->leftJoin('m_product_lokasi as e', 'a.id_product_lokasi_destination', '=', 'e.id_product_lokasi')
            ->where('a.incoming_hdr_id', $id)
            ->select(
                'a.*',
                'b.code_product',
                'b.nm_product',
                'c.nm_product_satuan',
                'd.complete_name as lokasi_source',
                'e.complete_name as lokasi_destination'
            )
            ->orderBy('a.id_product')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $data_header,
            'data_detail' => $data_detail
        ]);
    }

    /**
     * Receive Incoming
     */
    public function receive(Request $request, $id_incoming)
    {
        DB::beginTransaction();
        try {
            $data_barang = $request->input('data_barang');
            $header = DB::table('tb_incoming_hdr')->where('id', $id_incoming)->first();

            if (!$header) {
                return response()->json(['status' => false, 'message' => 'Incoming not found'], 404);
            }

            if ($data_barang && is_array($data_barang)) {
                foreach ($data_barang as $row) {
                    $id_dtl = $row['id_dtl'] ?? $row['id'];
                    if ($id_dtl) {
                        DB::table('tb_incoming_dtl')->where('id', $id_dtl)->update(['qty_terima' => 1]);
                        
                        // Insert stock skipped as per commented CI3 code
                    }
                }
            }

            // Check if there are any unreceived details
            $unreceived = DB::table('tb_incoming_dtl')
                ->where('incoming_hdr_id', $id_incoming)
                ->where('qty_terima', 0)
                ->get();

            if ($unreceived->count() > 0) {
                $periode = date('Ym');
                $code_incoming = $this->generateRunningNumber('IN', $periode);

                $id_incoming_baru = DB::table('tb_incoming_hdr')->insertGetId([
                    'code' => $code_incoming,
                    'id_po' => $header->id_po,
                    'id_suppliers' => $header->id_suppliers,
                    'status_incoming' => 'READY TO RECEIVE',
                    'date_create' => now(),
                    'f_assign_barcode' => true,
                    'f_print_barcode' => true,
                    'f_ok_receive' => true
                ]);

                foreach ($unreceived as $row) {
                    DB::table('tb_incoming_dtl')->insert([
                        'incoming_hdr_id' => $id_incoming_baru,
                        'id_product' => $row->id_product,
                        'qty' => 1,
                        'sn' => $row->sn,
                        'status' => 'Available',
                        'qty_terima' => 0,
                        'id_product_lokasi_source' => $row->id_product_lokasi_source,
                        'id_product_lokasi_destination' => $row->id_product_lokasi_destination
                    ]);
                }

                DB::table('tb_incoming_dtl')
                    ->where('incoming_hdr_id', $id_incoming)
                    ->where('qty_terima', 0)
                    ->delete();
            }

            DB::table('tb_incoming_hdr')->where('id', $id_incoming)->update([
                'status_incoming' => 'RECEIVED',
                'date_receive' => now(),
                'date_update' => now()
            ]);

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Receive incoming berhasil']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Assign Serial Number (SN)
     */
    public function assignSn(Request $request, $id_incoming)
    {
        DB::beginTransaction();
        try {
            $details = DB::table('tb_incoming_dtl')->where('incoming_hdr_id', $id_incoming)->get();

            foreach ($details as $row) {
                if (empty($row->sn)) {
                    $product = DB::table('m_product')->where('id_product', $row->id_product)->first();
                    if ($product) {
                        $id_product_kategori = $product->id_product_kategori ?? '00';
                        $id_product_sub_kategori = $product->id_product_sub_kategori ?? '00';
                        $tahun_mesin = date('y') + 3;
                        
                        $no_urut = str_pad($row->id, 5, '0', STR_PAD_LEFT);
                        $sn_baru = "{$id_product_kategori}.{$id_product_sub_kategori}.{$row->id_product}.{$tahun_mesin}.{$no_urut}";
                        
                        DB::table('tb_incoming_dtl')->where('id', $row->id)->update(['sn' => $sn_baru]);
                    }
                }
            }

            DB::table('tb_incoming_hdr')->where('id', $id_incoming)->update(['f_assign_barcode' => true]);

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Assign SN berhasil']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Print Barcode flag update
     */
    public function printBarcode(Request $request, $id_incoming)
    {
        DB::beginTransaction();
        try {
            DB::table('tb_incoming_hdr')->where('id', $id_incoming)->update([
                'f_print_barcode' => true,
                'f_ok_receive' => true
            ]);
            
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Print Barcode success']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    private function generateRunningNumber($prefix, $periode)
    {
        $last = DB::table('tb_incoming_hdr')
            ->where('code', 'like', $prefix . '-' . $periode . '-%')
            ->orderBy('id', 'desc')
            ->first();
            
        $code = $last ? $last->code : null;

        if ($code && preg_match('/-(\d{4})$/', $code, $matches)) {
            $last_number = intval($matches[1]);
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }

        return $prefix . '-' . $periode . '-' . str_pad($new_number, 4, '0', STR_PAD_LEFT);
    }
}
