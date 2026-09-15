<?php

namespace App\Http\Controllers\po;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PoController extends Controller
{
    /**
     * Get list of PO
     */
    public function index(Request $request)
    {
        $query = DB::table('tb_po_hdr')
            ->whereIn('status_po', ['DRAFT PO', 'PO PURCHASE'])
            ->select('id_po', 'code_po', 'date_po', 'status_po');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $terms = explode(',', $search);
                foreach ($terms as $term) {
                    $term = trim($term);
                    $q->orWhere('code_po', 'like', "%$term%")
                      ->orWhere('status_po', 'like', "%$term%");
                }
            });
        }

        $data = $query->orderBy('id_po', 'desc')->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    /**
     * Support data (Suppliers, Gudang, Products, Mata Uang)
     */
    public function supportData(Request $request)
    {
        $data_supplier = DB::table('m_suppliers')->get();
        $data_gudang = DB::table('m_gudang')->get();
        $data_product = DB::table('m_product')->get();
        $mata_uangs = DB::table('m_mata_uang')->get();

        return response()->json([
            'status' => true,
            'data_supplier' => $data_supplier,
            'data_gudang' => $data_gudang,
            'data_product' => $data_product,
            'mata_uangs' => $mata_uangs,
        ]);
    }

    /**
     * Show Data
     */
    public function show($id)
    {
        $data_header = DB::table('tb_po_hdr as a')
            ->join('m_suppliers as b', 'a.id_suppliers', '=', 'b.id_suppliers')
            ->join('m_gudang as c', 'a.id_gudang', '=', 'c.id_gudang')
            ->leftJoin('m_mata_uang as d', 'a.id_mata_uang', '=', 'd.id_mata_uang')
            ->leftJoin('m_product_lokasi as e', 'a.id_product_lokasi', '=', 'e.id_product_lokasi')
            ->where('a.id_po', $id)
            ->select('a.*', 'b.nm_suppliers', 'c.nm_gudang', 'd.name as mata_uang', 'e.nm_product_lokasi')
            ->first();

        if (!$data_header) {
            return response()->json(['status' => false, 'message' => 'Data not found'], 404);
        }

        $data_detail = DB::table('tb_po_dtl as a')
            ->join('m_product as b', 'a.id_product', '=', 'b.id_product')
            ->join('m_product_satuan as c', 'b.id_product_satuan', '=', 'c.id_product_satuan')
            ->where('a.id_po', $id)
            ->select('a.*', 'b.code_product', 'b.nm_product', 'b.product_deskripsi', 'c.nm_product_satuan')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $data_header,
            'data_detail' => $data_detail
        ]);
    }

    /**
     * Store Data
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_suppliers' => 'required',
            'id_gudang' => 'required',
            'date_po' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $date_po = date('Y-m-d', strtotime($request->date_po));
            $date_schdl = $request->date_schdl ? date('Y-m-d', strtotime($request->date_schdl)) : null;
            $periode = date('Ym', strtotime($date_po));
            
            $code_po = $this->generateRunningNumber('PO', $periode);
            $status_po = 'DRAFT PO';

            $id_po = DB::table('tb_po_hdr')->insertGetId([
                'code_po' => $code_po,
                'date_po' => $date_po,
                'status_po' => $status_po,
                'date_schdl' => $date_schdl,
                'id_suppliers' => $request->id_suppliers,
                'nm_suppliers' => $request->nm_suppliers,
                'id_gudang' => $request->id_gudang,
                'id_mata_uang' => $request->mata_uang,
                'partner_ref' => $request->partner_ref,
                'notes' => $request->notes,
                'amount_total' => 0,
                'id_product_lokasi' => $request->id_product_lokasi,
                'date_create' => now()
            ]);

            $amount_total = 0;
            $jml = $request->input('jml', 0);

            for ($i = 1; $i <= $jml; $i++) {
                $id_product = $request->input('id_product' . $i);
                if ($id_product) {
                    $code_product = $request->input('code_product' . $i);
                    $nm_product = $request->input('nm_product' . $i);
                    $product_deskripsi = $request->input('product_deskripsi' . $i);
                    $notes = $request->input('notes' . $i);
                    
                    $product_price = str_replace(',', '', $request->input('product_price' . $i));
                    $qty = str_replace(',', '', $request->input('nqty' . $i));
                    
                    $product_price = is_numeric($product_price) ? floatval($product_price) : 0;
                    $qty = is_numeric($qty) ? floatval($qty) : 0;

                    $amount_total += ($product_price * $qty);

                    $check_data_qty = DB::table('tb_po_dtl')->where('id_po', $id_po)->where('id_product', $id_product)->first();

                    if (!$check_data_qty) {
                        if ($qty > 0) {
                            DB::table('tb_po_dtl')->insert([
                                'id_po' => $id_po,
                                'id_product' => $id_product,
                                'code_product' => $code_product,
                                'nm_product' => $nm_product,
                                'product_deskripsi' => $product_deskripsi,
                                'qty' => $qty,
                                'product_price' => $product_price,
                                'notes' => $notes
                            ]);
                        }
                    } else {
                        if ($qty > 0) {
                            $id_po_dtl = $check_data_qty->id_po_dtl;
                            DB::table('tb_po_dtl')
                                ->where('id_po_dtl', $id_po_dtl)
                                ->where('id_product', $id_product)
                                ->update(['qty' => DB::raw("qty + $qty")]);
                        }
                    }
                }
            }

            DB::table('tb_po_hdr')->where('id_po', $id_po)->update(['amount_total' => $amount_total]);

            DB::commit();
            Log::info('Simpan Data PO Kode : ' . $id_po);

            return response()->json([
                'status' => true,
                'kode' => $code_po,
                'id_po' => $id_po,
                'message' => 'Success'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update Data
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'id_suppliers' => 'required',
            'id_gudang' => 'required',
            'date_po' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $id_po = $id;
            $date_po = date('Y-m-d', strtotime($request->date_po));
            $date_schdl = $request->date_schdl ? date('Y-m-d', strtotime($request->date_schdl)) : null;

            $data_update = [
                'date_po' => $date_po,
                'date_schdl' => $date_schdl,
                'id_suppliers' => $request->id_suppliers,
                'nm_suppliers' => $request->nm_suppliers,
                'id_gudang' => $request->id_gudang,
                'id_mata_uang' => $request->mata_uang,
                'partner_ref' => $request->partner_ref,
                'notes' => $request->notes,
                'id_product_lokasi' => $request->id_product_lokasi
            ];

            if ($request->hasFile('link_file')) {
                $file = $request->file('link_file');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('assets/upload'), $filename);
                $data_update['link_file'] = $filename;

                $data_po = DB::table('tb_po_hdr')->where('id_po', $id_po)->first();
                if ($data_po && !empty($data_po->link_file) && file_exists(public_path('assets/upload/' . $data_po->link_file))) {
                    unlink(public_path('assets/upload/' . $data_po->link_file));
                }
            }

            DB::table('tb_po_hdr')->where('id_po', $id_po)->update($data_update);
            DB::table('tb_po_dtl')->where('id_po', $id_po)->delete();

            $amount_total = 0;
            $jml = $request->input('jml', 0);

            for ($i = 1; $i <= $jml; $i++) {
                $id_product = $request->input('id_product' . $i);
                if ($id_product) {
                    $code_product = $request->input('code_product' . $i);
                    $nm_product = $request->input('nm_product' . $i);
                    $product_deskripsi = $request->input('product_deskripsi' . $i);
                    $notes = $request->input('notes' . $i);
                    
                    $product_price = str_replace(',', '', $request->input('product_price' . $i));
                    $qty = str_replace(',', '', $request->input('nqty' . $i));
                    
                    $product_price = is_numeric($product_price) ? floatval($product_price) : 0;
                    $qty = is_numeric($qty) ? floatval($qty) : 0;

                    $amount_total += ($product_price * $qty);

                    if ($qty > 0) {
                        DB::table('tb_po_dtl')->insert([
                            'id_po' => $id_po,
                            'id_product' => $id_product,
                            'code_product' => $code_product,
                            'nm_product' => $nm_product,
                            'product_deskripsi' => $product_deskripsi,
                            'qty' => $qty,
                            'product_price' => $product_price,
                            'notes' => $notes
                        ]);
                    }
                }
            }

            DB::table('tb_po_hdr')->where('id_po', $id_po)->update(['amount_total' => $amount_total]);

            DB::commit();
            Log::info('Update Data PO Kode : ' . $id_po);

            return response()->json([
                'status' => true,
                'id_po' => $id_po,
                'message' => 'Success'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Confirm
     */
    public function confirm(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $id_po = $id;
            $data_header = DB::table('tb_po_hdr')->where('id_po', $id_po)->first();
            
            if (!$data_header) {
                return response()->json(['status' => false, 'message' => 'Data not found'], 404);
            }

            DB::table('tb_po_hdr')->where('id_po', $id_po)->update([
                'status_po' => 'PO PURCHASE'
            ]);

            $data_barang = DB::table('tb_po_dtl')->where('id_po', $id_po)->get();

            $periode = date('Ym');
            $code_incoming = $this->generateRunningNumber('IN', $periode);

            $id_incoming = DB::table('tb_incoming_hdr')->insertGetId([
                'code' => $code_incoming,
                'id_po' => $id_po,
                'id_suppliers' => $data_header->id_suppliers,
                'status_incoming' => 'Ready to Receive',
                'date_create' => now()
            ]);

            foreach ($data_barang as $row) {
                DB::table('tb_incoming_dtl')->insert([
                    'incoming_hdr_id' => $id_incoming,
                    'id_product' => $row->id_product,
                    'qty' => $row->qty,
                    'status' => 'Available'
                ]);
            }

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Success Confirmed'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cancel
     */
    public function cancel(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            DB::table('tb_po_hdr')->where('id_po', $id)->update([
                'status_po' => 'CANCEL'
            ]);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Success Canceled'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    private function generateRunningNumber($prefix, $periode)
    {
        if ($prefix === 'IN') {
            $last = DB::table('tb_incoming_hdr')
                ->where('code', 'like', $prefix . '-' . $periode . '-%')
                ->orderBy('id_incoming', 'desc')
                ->first();
            $code = $last ? $last->code : null;
        } else {
            $last = DB::table('tb_po_hdr')
                ->where('code_po', 'like', $prefix . '-' . $periode . '-%')
                ->orderBy('id_po', 'desc')
                ->first();
            $code = $last ? $last->code_po : null;
        }

        if ($code && preg_match('/-(\d{4})$/', $code, $matches)) {
            $last_number = intval($matches[1]);
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }

        return $prefix . '-' . $periode . '-' . str_pad($new_number, 4, '0', STR_PAD_LEFT);
    }
}
