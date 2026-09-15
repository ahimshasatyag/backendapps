<?php

namespace App\Http\Controllers\purchaserequisitions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class PurchaserequisitionController extends Controller
{
    /**
     * Get list of Purchase Requisitions
     */
    public function index(Request $request)
    {
        $query = DB::table('tb_pr_hdr')
            ->select('id_pr', 'code_pr', 'username', 'date_request', 'date_deadline', 'status_pr');

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $terms = explode(',', $search);
                foreach ($terms as $term) {
                    $term = trim($term);
                    $q->orWhere('code_pr', 'like', "%$term%")
                      ->orWhere('username', 'like', "%$term%")
                      ->orWhere('status_pr', 'like', "%$term%");
                }
            });
        }

        $data = $query->orderBy('id_pr', 'desc')->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    /**
     * Support data (Products, Gudang, Users)
     */
    public function supportData(Request $request)
    {
        $data_product = DB::table('m_product')->get();
        $data_gudang = DB::table('m_gudang')->get();
        $data_users = DB::table('m_users')->get();

        return response()->json([
            'status' => true,
            'data_product' => $data_product,
            'data_gudang' => $data_gudang,
            'data_users' => $data_users
        ]);
    }

    /**
     * Detail Barang
     */
    public function detailBarang(Request $request)
    {
        $id_product = $request->id_product;
        $data = DB::table('m_product as b')
            ->join('m_product_satuan as c', 'b.id_product_satuan', '=', 'c.id_product_satuan')
            ->where('b.id_product', $id_product)
            ->select('b.id_product', 'b.code_product', 'b.nm_product', 'c.nm_product_satuan')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Show Edit / Detail PR
     */
    public function show($id)
    {
        $data_header = DB::table('tb_pr_hdr')->where('id_pr', $id)->first();

        if (!$data_header) {
            return response()->json(['status' => false, 'message' => 'Data not found'], 404);
        }

        // Fetch related PO info if exists (from any detail item that has been converted to PO)
        $po_info = DB::table('tb_pr_dtl')
            ->join('tb_po_dtl', 'tb_pr_dtl.id_po_dtl', '=', 'tb_po_dtl.id_po_dtl')
            ->join('tb_po_hdr', 'tb_po_dtl.id_po', '=', 'tb_po_hdr.id_po')
            ->where('tb_pr_dtl.id_pr', $id)
            ->select('tb_po_hdr.code_po', 'tb_po_hdr.status_po')
            ->first();

        if ($po_info) {
            $data_header->code_po = $po_info->code_po;
            $data_header->status_po = $po_info->status_po;
        }

        $data_detail = DB::table('tb_pr_dtl as a')
            ->join('m_product as b', 'a.id_product', '=', 'b.id_product')
            ->join('m_product_satuan as c', 'b.id_product_satuan', '=', 'c.id_product_satuan')
            ->where('a.id_pr', $id)
            ->select('a.id_product', 'a.qty', 'a.note', 'b.nm_product', 'c.nm_product_satuan')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $data_header,
            'data_detail' => $data_detail
        ]);
    }

    /**
     * Store new PR
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'date_request' => 'required|date',
            'date_deadline' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $username = $request->username;
            $date_request = date('Y-m-d', strtotime($request->date_request));
            $date_deadline = date('Y-m-d', strtotime($request->date_deadline));
            $periode = date('Ym', strtotime($date_request));

            $code_pr = $this->generateRunningNumber('PR', $periode);

            $id_pr = DB::table('tb_pr_hdr')->insertGetId([
                'code_pr' => $code_pr,
                'username' => $username,
                'date_request' => $date_request,
                'date_deadline' => $date_deadline,
                'status_pr' => null
            ]);

            $jml = $request->input('jml', 0);
            for ($i = 1; $i <= $jml; $i++) {
                $id_product = $request->input('id_product' . $i);
                if ($id_product) {
                    $qty = $request->input('qty' . $i);
                    $note = $request->input('note' . $i);

                    $product = DB::table('m_product')->where('id_product', $id_product)->first();

                    DB::table('tb_pr_dtl')->insert([
                        'id_pr' => $id_pr,
                        'id_product' => $id_product,
                        'code_product' => $product ? $product->code_product : null,
                        'nm_product' => $product ? $product->nm_product : null,
                        'product_deskripsi' => $product ? $product->product_deskripsi : null,
                        'qty' => $qty,
                        'note' => $note,
                        'qty_po' => 0
                    ]);
                }
            }

            DB::commit();
            Log::info('Simpan Data PR Kode : ' . $code_pr);

            return response()->json([
                'status' => true,
                'kode' => $code_pr,
                'id_pr' => $id_pr,
                'message' => 'Success'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update PR
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required',
            'date_request' => 'required|date',
            'date_deadline' => 'required|date'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $id_pr = $id;
            $username = $request->username;
            $date_request = date('Y-m-d', strtotime($request->date_request));
            $date_deadline = date('Y-m-d', strtotime($request->date_deadline));

            DB::table('tb_pr_hdr')->where('id_pr', $id_pr)->update([
                'username' => $username,
                'date_request' => $date_request,
                'date_deadline' => $date_deadline
            ]);

            DB::table('tb_pr_dtl')->where('id_pr', $id_pr)->delete();

            $jml = $request->input('jml', 0);
            for ($i = 1; $i <= $jml; $i++) {
                $id_product = $request->input('id_product' . $i);
                if ($id_product) {
                    $qty = $request->input('qty' . $i);
                    $note = $request->input('note' . $i);

                    $product = DB::table('m_product')->where('id_product', $id_product)->first();

                    DB::table('tb_pr_dtl')->insert([
                        'id_pr' => $id_pr,
                        'id_product' => $id_product,
                        'code_product' => $product ? $product->code_product : null,
                        'nm_product' => $product ? $product->nm_product : null,
                        'product_deskripsi' => $product ? $product->product_deskripsi : null,
                        'qty' => $qty,
                        'note' => $note,
                        'qty_po' => 0
                    ]);
                }
            }

            DB::commit();
            Log::info('Update Data PR ID : ' . $id_pr);

            return response()->json([
                'status' => true,
                'id_pr' => $id_pr,
                'message' => 'Success'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Ajukan PR
     */
    public function ajukan($id)
    {
        DB::beginTransaction();
        try {
            DB::table('tb_pr_hdr')->where('id_pr', $id)->update([
                'status_pr' => 'PR'
            ]);
            
            DB::commit();
            return response()->json([
                'status' => true,
                'kode' => 'Status : PR',
                'message' => 'Berhasil mengajukan PR'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * List PR (Ready for PO)
     */
    public function listPr(Request $request)
    {
        $data = DB::table('tb_pr_dtl as a')
            ->join('tb_pr_hdr as b', 'a.id_pr', '=', 'b.id_pr')
            ->join('m_product as c', 'a.id_product', '=', 'c.id_product')
            ->join('m_users as d', 'b.username', '=', 'd.username')
            ->where('b.status_pr', 'PR')
            ->where('a.qty_po', 0)
            ->select('a.id_pr_dtl', 'a.id_pr', 'a.id_product', 'c.nm_product', 'c.code_product', 'd.nm_users', 'b.code_pr', 'a.qty')
            ->get();

        return response()->json([
            'status' => true,
            'data_pr' => $data
        ]);
    }

    /**
     * Simpan PO
     */
    public function simpanPo(Request $request)
    {
        $data_id_pr_dtl = $request->input('data_id_pr_dtl');
        $status = false;
        $code_po = null;
        $id_po = null;

        if ($data_id_pr_dtl && is_array($data_id_pr_dtl)) {
            DB::beginTransaction();
            try {
                $periode = date('Ym');
                $code_po = $this->generateRunningNumber('PO', $periode);
                $date_po = date('Y-m-d');
                $status_po = 'QUOTATION';

                $id_po = DB::table('tb_po_hdr')->insertGetId([
                    'code_po' => $code_po,
                    'date_po' => $date_po,
                    'status_po' => $status_po
                ]);

                foreach ($data_id_pr_dtl as $row) {
                    $id_product = $row['id_product'] ?? null;
                    $id_pr_dtl = $row['id_pr_dtl'] ?? null;
                    $qty_po = $row['qty_po'] ?? 0;

                    if ($id_product && $id_pr_dtl) {
                        $check_data_qty = DB::table('tb_po_dtl')
                            ->where('id_po', $id_po)
                            ->where('id_product', $id_product)
                            ->first();

                        if (!$check_data_qty) {
                            if ($qty_po > 0) {
                                $product = DB::table('m_product')->where('id_product', $id_product)->first();

                                $id_po_dtl = DB::table('tb_po_dtl')->insertGetId([
                                    'id_po' => $id_po,
                                    'id_product' => $id_product,
                                    'code_product' => $product ? $product->code_product : null,
                                    'nm_product' => $product ? $product->nm_product : null,
                                    'product_deskripsi' => $product ? $product->product_deskripsi : null,
                                    'qty' => $qty_po
                                ]);

                                DB::table('tb_pr_dtl')->where('id_pr_dtl', $id_pr_dtl)->update([
                                    'qty_po' => $qty_po,
                                    'id_po_dtl' => $id_po_dtl
                                ]);
                            }
                        } else {
                            if ($qty_po > 0) {
                                $id_po_dtl = $check_data_qty->id_po_dtl;

                                DB::table('tb_po_dtl')
                                    ->where('id_po_dtl', $id_po_dtl)
                                    ->where('id_product', $id_product)
                                    ->increment('qty', $qty_po);

                                DB::table('tb_pr_dtl')->where('id_pr_dtl', $id_pr_dtl)->update([
                                    'qty_po' => $qty_po,
                                    'id_po_dtl' => $id_po_dtl
                                ]);
                            }
                        }
                    }
                }
                
                $status = true;
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
            }
        }

        return response()->json([
            'status' => $status,
            'code_po' => $code_po,
            'id_po' => $id_po
        ]);
    }

    /**
     * Generate Running Number for PR / PO
     */
    private function generateRunningNumber($prefix, $periode)
    {
        if ($prefix === 'PO') {
            $last = DB::table('tb_po_hdr')
                ->where('code_po', 'like', $prefix . '-' . $periode . '-%')
                ->orderBy('id_po', 'desc')
                ->first();
            $code = $last ? $last->code_po : null;
        } else {
            $last = DB::table('tb_pr_hdr')
                ->where('code_pr', 'like', $prefix . '-' . $periode . '-%')
                ->orderBy('id_pr', 'desc')
                ->first();
            $code = $last ? $last->code_pr : null;
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
