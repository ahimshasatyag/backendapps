<?php

namespace App\Http\Controllers\Do;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoController extends Controller
{
    /**
     * Get list of Delivery Orders (DO)
     */
    public function index(Request $request)
    {
        $query = DB::table('tb_do_hdr as a')
            ->leftJoin('m_customers as b', 'a.id_customers', '=', 'b.id_customers')
            ->leftJoin('m_karyawan as c', 'a.id_karyawan', '=', 'c.id_karyawan')
            ->leftJoin('tb_so_hdr as d', 'a.id_so', '=', 'd.id_so')
            ->select(
                'a.id_do',
                'a.code_do',
                'a.date_do',
                'b.nm_customers',
                'd.code_so',
                'a.status_do'
            )
            ->whereIn('a.status_do', ['DRAFT DELIVERY ORDER', 'WAITING AVAILABILITY', 'READY TO DELIVER', 'DELIVERED'])
            ->where('a.flag_cancel', 0)
            ->where(function($q) {
                $q->where('d.flag_cancel', 0)->orWhereNull('d.flag_cancel');
            });
            
        if ($request->has('search') && !empty($request->search)) {
            $search = explode(',', $request->search);
            $query->where(function ($q) use ($search) {
                foreach ($search as $term) {
                    $q->orWhere('a.code_do', 'like', "%$term%")
                      ->orWhere('b.nm_customers', 'like', "%$term%")
                      ->orWhere('d.code_so', 'like', "%$term%")
                      ->orWhere('a.status_do', 'like', "%$term%");
                }
            });
        }

        $do_list = $query->orderBy('a.date_do', 'desc')
                    ->orderBy('a.id_do', 'desc')
                    ->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'data' => $do_list
        ]);
    }

    /**
     * Get detail of a specific Delivery Order
     */
    public function show($id)
    {
        $do = DB::table('tb_do_hdr as a')
            ->leftJoin('m_users as b', 'a.username_create', '=', 'b.username')
            ->leftJoin('m_karyawan as c', 'a.id_karyawan', '=', 'c.id_karyawan')
            ->leftJoin('m_customers as d', 'a.id_customers', '=', 'd.id_customers')
            ->leftJoin('tb_so_hdr as g', 'a.id_so', '=', 'g.id_so')
            ->leftJoin('m_type_bayar_hdr as e', 'g.id_type_pembayaran', '=', 'e.id_type_pembayaran')
            ->leftJoin('m_type_bayar_dtl as f', 'g.id_cara_pembayaran', '=', 'f.id_cara_pembayaran')
            ->select(
                'a.id_do',
                'a.code_do',
                'a.date_do',
                'a.date_estimasi',
                'c.nm_karyawan',
                'd.nm_customers',
                'd.customers_email',
                'd.customers_phone',
                'b.nm_users',
                'e.nm_type_pembayaran',
                'f.nm_cara_pembayaran',
                'g.flag_ppn',
                'a.id_karyawan',
                'c.id_karyawan_posisi',
                'g.no_po_cust',
                'a.date_delivery',
                'g.id_type_pembayaran',
                'g.id_cara_pembayaran',
                'g.code_so',
                'a.keterangan',
                'a.customers_address',
                'a.status_do',
                'g.flag_payment'
            )
            ->where('a.id_do', $id)
            ->first();

        if (!$do) {
            return response()->json(['status' => false, 'message' => 'Delivery Order not found'], 404);
        }

        // Details
        $details = DB::table('tb_do_dtl as a')
            ->join('m_product as b', 'a.id_product', '=', 'b.id_product')
            ->join('m_product_satuan as c', 'b.id_product_satuan', '=', 'c.id_product_satuan')
            ->select(
                'a.id_do_dtl',
                'a.id_product',
                'b.code_product',
                'b.nm_product',
                'b.product_deskripsi',
                'a.nqty',
                'a.nbarcode',
                'c.nm_product_satuan',
                'a.leasing_tahun',
                'a.leasing_plat'
            )
            ->where('a.id_do', $id)
            ->get();

        $do->details = $details;

        return response()->json([
            'status' => true,
            'data' => $do
        ]);
    }

    /**
     * Update Delivery Order
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $date_do = $request->date_do ? date("Y-m-d", strtotime($request->date_do)) : null;
            $date_estimasi = $request->date_estimasi ? date("Y-m-d", strtotime($request->date_estimasi)) : null;
            $date_delivery = $request->date_delivery ? date("Y-m-d", strtotime($request->date_delivery)) : null;
            $keterangan = $request->keterangan;

            DB::table('tb_do_hdr')->where('id_do', $id)->update([
                'date_do' => $date_do,
                'date_estimasi' => $date_estimasi,
                'date_delivery' => $date_delivery,
                'keterangan' => $keterangan
            ]);

            // Update details
            if ($request->has('details') && is_array($request->details)) {
                foreach ($request->details as $dtl) {
                    if (isset($dtl['id_do_dtl']) && isset($dtl['id_product'])) {
                        DB::table('tb_do_dtl')
                            ->where('id_do_dtl', $dtl['id_do_dtl'])
                            ->where('id_product', $dtl['id_product'])
                            ->update([
                                'nbarcode' => $dtl['nbarcode'] ?? null,
                                'leasing_tahun' => $dtl['leasing_tahun'] ?? null,
                                'leasing_plat' => $dtl['leasing_plat'] ?? null,
                            ]);
                    }
                }
            }

            DB::commit();

            return response()->json([
                'status' => true, 
                'message' => 'Delivery Order successfully updated'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to update Delivery Order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Confirm DO (Status to WAITING AVAILABILITY, generate SJ number)
     */
    public function confirm(Request $request)
    {
        $id_do = $request->id_do;
        $do = DB::table('tb_do_hdr')->where('id_do', $id_do)->first();

        if (!$do) {
            return response()->json(['status' => false, 'message' => 'Delivery Order not found'], 404);
        }

        DB::beginTransaction();
        try {
            $date_do = $do->date_do;
            $periode = date('Ym');

            if (date('Y', strtotime($date_do)) == '2010') {
                $periode = date('Ym', strtotime($date_do));
            }

            $prefix = "SJ-EMM/" . substr($periode, 0, 4) . "/" . substr($periode, 4, 2) . "/";
            $code_do_baru = $prefix . str_pad($this->getRunningNumber('SJ', $periode), 5, '0', STR_PAD_LEFT);
            $code_do_lama = $do->code_do;

            DB::table('tb_do_hdr')->where('id_do', $id_do)->update([
                'status_do' => 'WAITING AVAILABILITY',
                'code_do' => $code_do_baru,
                'code_do_tmp' => $code_do_lama
            ]);

            DB::commit();

            return response()->json(['status' => true, 'message' => 'DO confirmed successfully, status updated to WAITING AVAILABILITY']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to confirm DO: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Check availability (Status to READY TO DELIVER)
     */
    public function cekAvailability(Request $request)
    {
        $id_do = $request->id_do;
        
        DB::table('tb_do_hdr')->where('id_do', $id_do)->update([
            'status_do' => 'READY TO DELIVER'
        ]);

        return response()->json(['status' => true, 'message' => 'Status updated to READY TO DELIVER']);
    }

    /**
     * Get available SN for a specific product
     */
    public function getAvailableSn($id_product)
    {
        $sn_list = DB::table('m_product_sn')
            ->select('sn')
            ->where('id_product', $id_product)
            ->where('nqty', '>', 0)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $sn_list
        ]);
    }

    /**
     * Set DO as Delivered and update SN
     */
    public function delivered(Request $request)
    {
        $id_do = $request->id_do;
        
        $data_barang = DB::table('tb_do_dtl')->where('id_do', $id_do)->get();
        if ($data_barang->count() > 0) {
            $status = true;

            foreach ($data_barang as $row) {
                $id_product = $row->id_product;
                $sn = $row->nbarcode;

                if (empty($sn)) {
                    $status = false;
                    break;
                }

                $cek_barang = DB::table('m_product_sn')
                    ->where('id_product', $id_product)
                    ->where('sn', $sn)
                    ->where('nqty', 0)
                    ->first();

                if ($cek_barang) {
                    $status = false;
                    break;
                }
            }

            if ($status) {
                DB::beginTransaction();
                try {
                    foreach ($data_barang as $row) {
                        $id_product = $row->id_product;
                        $sn = $row->nbarcode;

                        DB::table('m_product_sn')
                            ->where('id_product', $id_product)
                            ->where('sn', $sn)
                            ->update([
                                'nqty' => 0,
                                'date_update' => now()
                            ]);
                    }

                    DB::table('tb_do_hdr')->where('id_do', $id_do)->update([
                        'status_do' => 'DELIVERED',
                        // 'date_delivery' => now() // Based on CI logic
                    ]);

                    DB::commit();
                    return response()->json(['status' => true, 'message' => 'DO successfully set to DELIVERED']);
                } catch (\Exception $e) {
                    DB::rollBack();
                    return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
                }
            } else {
                return response()->json(['status' => false, 'message' => 'Validation failed: Invalid SN or empty barcode']);
            }
        }

        return response()->json(['status' => false, 'message' => 'No items found in DO']);
    }

    /**
     * Cancel DO
     */
    public function cancel(Request $request)
    {
        $id_do = $request->id_do;
        $alasan = $request->alasan;
        
        $do = DB::table('tb_do_hdr')->where('id_do', $id_do)->first();
        if (!$do) {
            return response()->json(['status' => false, 'message' => 'Delivery Order not found'], 404);
        }

        DB::beginTransaction();
        try {
            DB::table('tb_do_hdr')->where('id_do', $id_do)->update([
                'status_do' => 'OUTSTANDING QUOTATION', // Following Mmaster CI logic
            ]);

            // Create Approval for cancellation (using ID 1 assuming it's default from Mmaster)
            $approve_hdr = DB::table('m_approve_hdr')->where('id_approve', 1)->first();
            
            if ($approve_hdr) {
                DB::table('tb_approval')->insert([
                    'id_approve' => $approve_hdr->id_approve,
                    'date_request' => now(),
                    'username_request' => $request->username ?? 'system',
                    'nm_module' => $approve_hdr->nm_module,
                    'nm_table' => $approve_hdr->nm_table,
                    'key_table' => $approve_hdr->key_table,
                    'id_key_table' => $id_do,
                    'code_key_table' => $do->code_do,
                    'status_table' => $approve_hdr->status_table,
                    'action_approve' => $approve_hdr->action_approve,
                    'action_canceled' => $approve_hdr->action_canceled,
                    'status_approve' => '0',
                    'alasan' => $alasan,
                    'id_menu' => '11001'
                ]);
            }

            DB::commit();

            return response()->json(['status' => true, 'message' => 'DO canceled and approval requested']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to cancel DO: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Split DO
     */
    public function split(Request $request)
    {
        $id_do = $request->id_do;
        $details_to_split = $request->details; // Array of id_do_dtl and id_product
        
        $do_lama = DB::table('tb_do_hdr')->where('id_do', $id_do)->first();
        
        if (!$do_lama || empty($details_to_split) || !is_array($details_to_split)) {
            return response()->json(['status' => false, 'message' => 'Invalid DO or details'], 400);
        }
        
        DB::beginTransaction();
        try {
            $periode = date('Ym');
            $prefix = "DO-EMM/" . substr($periode, 0, 4) . "/" . substr($periode, 4, 2) . "/";
            $code_do = $prefix . str_pad($this->getRunningNumber('DO', $periode), 5, '0', STR_PAD_LEFT);
            
            $id_do_baru = DB::table('tb_do_hdr')->insertGetId([
                'code_do' => $code_do,
                'id_so' => $do_lama->id_so,
                'date_do' => date('Y-m-d'),
                'id_karyawan' => $do_lama->id_karyawan,
                'id_customers' => $do_lama->id_customers,
                'date_estimasi' => $do_lama->date_estimasi,
                'customers_address' => $do_lama->customers_address,
                'username_create' => $do_lama->username_create,
                'status_do' => 'DRAFT DELIVERY ORDER',
            ]);
            
            foreach ($details_to_split as $dtl) {
                if (isset($dtl['id_do_dtl'])) {
                    $data_do_dtl = DB::table('tb_do_dtl')->where('id_do_dtl', $dtl['id_do_dtl'])->first();
                    if ($data_do_dtl) {
                        DB::table('tb_do_dtl')->insert([
                            'id_do' => $id_do_baru,
                            'id_product' => $data_do_dtl->id_product,
                            'nqty' => $data_do_dtl->nqty,
                            'nbarcode' => $data_do_dtl->nbarcode,
                            'leasing_tahun' => $data_do_dtl->leasing_tahun,
                            'leasing_plat' => $data_do_dtl->leasing_plat,
                        ]);
                        
                        // Delete old dtl
                        DB::table('tb_do_dtl')->where('id_do_dtl', $dtl['id_do_dtl'])->delete();
                    }
                }
            }
            
            DB::commit();
            return response()->json(['status' => true, 'message' => 'DO splitted successfully', 'new_code_do' => $code_do, 'new_id_do' => $id_do_baru]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to split DO: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Revise DO
     */
    public function revisi(Request $request)
    {
        $id_do = $request->id_do;
        
        $cek_reference = DB::table('tb_do_hdr')->where('id_do', $id_do)->first();
        if (!$cek_reference) {
            return response()->json(['status' => false, 'message' => 'Delivery Order not found'], 404);
        }
        
        $id_do_lama = $id_do;
        if ($cek_reference->id_do_reference != null) {
            $id_do = $cek_reference->id_do_reference;
        }
        
        DB::beginTransaction();
        try {
            $jml_so = DB::table('tb_do_hdr')->where('id_do_reference', $id_do)->count();
            $jml_so++;
            
            $code_do_revisi = "-R" . $jml_so;
            $code_do_baru = substr($cek_reference->code_do, 0, 20) . $code_do_revisi;
            
            $id_do_baru = DB::table('tb_do_hdr')->insertGetId([
                'code_do' => $code_do_baru,
                'date_do' => $cek_reference->date_do,
                'id_karyawan' => $cek_reference->id_karyawan,
                'id_customers' => $cek_reference->id_customers,
                'date_estimasi' => $cek_reference->date_estimasi,
                'id_type_pembayaran' => $cek_reference->id_type_pembayaran,
                'id_cara_pembayaran' => $cek_reference->id_cara_pembayaran,
                'flag_ppn' => $cek_reference->flag_ppn,
                'id_waktu_bayar' => $cek_reference->id_waktu_bayar,
                'vcurrency' => $cek_reference->vcurrency,
                'nkurs' => $cek_reference->nkurs,
                'ndp_amount' => $cek_reference->ndp_amount,
                'ndp_persen' => $cek_reference->ndp_persen,
                'nppn_amount' => $cek_reference->nppn_amount,
                'ntenor' => $cek_reference->ntenor,
                'ntenor_amount' => $cek_reference->ntenor_amount,
                'ntot_price_gross_amount' => $cek_reference->ntot_price_gross_amount,
                'ntot_price_netto_amount' => $cek_reference->ntot_price_netto_amount,
                'username_create' => $cek_reference->username_create,
                'status_so' => 'DRAFT QUOTATION',
                'flag_payment' => $cek_reference->flag_payment,
                'flag_availability' => $cek_reference->flag_availability,
                'flag_pass_so' => $cek_reference->flag_pass_so,
                'customers_address' => $cek_reference->customers_address,
                'id_do_reference' => $id_do,
            ]);
            
            $data_barang = DB::table('tb_do_dtl')->where('id_do', $id_do_lama)->get();
            foreach ($data_barang as $row) {
                DB::table('tb_do_dtl')->insert([
                    'id_do' => $id_do_baru,
                    'id_product' => $row->id_product,
                    'status_barang' => $row->status_barang,
                    'indent_amount' => $row->indent_amount,
                    'product_price' => $row->product_price,
                    'product_price_old' => $row->product_price_old,
                    'nqty' => $row->nqty,
                    'ntot_product_price' => $row->ntot_product_price,
                    'ntot_product_price_old' => $row->ntot_product_price_old,
                    'ndiskon_persen' => $row->ndiskon_persen,
                    'ndiskon_amount' => $row->ndiskon_amount,
                    'ntax' => $row->ntax,
                    'ntot_product_price_netto' => $row->ntot_product_price_netto,
                ]);
            }
            
            DB::commit();
            return response()->json(['status' => true, 'id_do' => $id_do_baru, 'message' => 'DO revised successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to revise DO: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get running number for DO, SJ
     */
    private function getRunningNumber($id_counter, $periode)
    {
        $year = substr($periode, 0, 4);
        $cek = DB::table('m_counter')
            ->where('id_counter', $id_counter)
            ->whereRaw('SUBSTRING(periode, 1, 4) = ?', [$year])
            ->first();

        if ($cek) {
            $terakhir = $cek->no_counter + 1;
            DB::table('m_counter')
                ->where('id_counter', $id_counter)
                ->whereRaw('SUBSTRING(periode, 1, 4) = ?', [$year])
                ->update(['no_counter' => $terakhir]);
            return $terakhir;
        } else {
            DB::table('m_counter')->insert([
                'id_counter' => $id_counter,
                'periode' => $periode,
                'no_counter' => 1
            ]);
            return 1;
        }
    }
}
