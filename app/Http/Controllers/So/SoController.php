<?php

namespace App\Http\Controllers\So;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SoController extends Controller
{
    /**
     * Get list of Sales Orders (SO)
     */
    public function index(Request $request)
    {
        $query = DB::table('tb_so_hdr as a')
            ->leftJoin('m_customers as b', 'a.id_customers', '=', 'b.id_customers')
            ->leftJoin('m_karyawan as c', 'a.id_karyawan', '=', 'c.id_karyawan')
            ->select(
                'a.id_so',
                'a.code_so',
                'a.date_so',
                'b.nm_customers',
                'c.nm_karyawan',
                'a.no_po_cust',
                'a.status_so'
            )
            ->whereIn('a.status_so', ['SALE TO INVOICE', 'SALES ORDER'])
            ->where('a.flag_cancel', 0);
            
        if ($request->has('search') && !empty($request->search)) {
            $search = explode(',', $request->search);
            $query->where(function ($q) use ($search) {
                foreach ($search as $term) {
                    $q->orWhere('a.code_so', 'like', "%$term%")
                      ->orWhere('b.nm_customers', 'like', "%$term%")
                      ->orWhere('c.nm_karyawan', 'like', "%$term%")
                      ->orWhere('a.status_so', 'like', "%$term%");
                }
            });
        }

        $so = $query->orderBy('a.date_so', 'desc')
                    ->orderBy('a.id_so', 'desc')
                    ->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'data' => $so
        ]);
    }

    /**
     * Get detail of a specific Sales Order
     */
    public function show($id)
    {
        $so = DB::table('tb_so_hdr as a')
            ->leftJoin('m_users as b', 'a.username_create', '=', 'b.username')
            ->leftJoin('m_karyawan as c', 'a.id_karyawan', '=', 'c.id_karyawan')
            ->leftJoin('m_customers as d', 'a.id_customers', '=', 'd.id_customers')
            ->leftJoin('m_type_bayar_hdr as e', 'a.id_type_pembayaran', '=', 'e.id_type_pembayaran')
            ->leftJoin('m_type_bayar_dtl as f', 'a.id_cara_pembayaran', '=', 'f.id_cara_pembayaran')
            ->leftJoin('m_waktu_bayar as g', 'a.id_waktu_bayar', '=', 'g.id_waktu_bayar')
            ->select(
                'a.*',
                'c.nm_karyawan',
                'c.id_karyawan_posisi',
                'c.karyawan_email',
                'd.nm_customers',
                'd.customers_address',
                'd.customers_email',
                'd.customers_phone',
                'd.customers_mobile',
                'b.nm_users',
                'e.nm_type_pembayaran',
                'f.nm_cara_pembayaran',
                'g.nm_waktu_bayar'
            )
            ->where('a.id_so', $id)
            ->first();

        if (!$so) {
            return response()->json(['status' => false, 'message' => 'Sales Order not found'], 404);
        }

        // Details
        $details = DB::table('tb_so_dtl as a')
            ->join('m_product as b', 'a.id_product', '=', 'b.id_product')
            ->join('m_product_satuan as c', 'b.id_product_satuan', '=', 'c.id_product_satuan')
            ->join('m_product_price as d', function($join) {
                $join->on('a.id_product', '=', 'd.id_product')
                     ->on('b.id_product', '=', 'd.id_product');
            })
            ->select(
                'a.id_product',
                'b.code_product',
                'b.nm_product',
                'b.product_deskripsi',
                'a.status_barang',
                'a.indent_amount',
                'a.product_price',
                'a.nqty',
                'c.nm_product_satuan',
                'd.delivery_term'
            )
            ->where('a.id_so', $id)
            ->distinct()
            ->get();

        foreach ($details as $detail) {
            $options = DB::table('tb_so_dtl_options')
                ->select('id_product_price_opt', 'nm_product_opt', 'amount', 'qty')
                ->where('id_product', $detail->id_product)
                ->where('id_so', $id)
                ->get();
            $detail->options = $options;
        }

        $so->details = $details;

        // Warranty / Ext Garansi
        try {
            $so->ext_garansi = DB::table('tb_so_ext_garansi')->where('id_so', $id)->get();
        } catch (\Exception $e) {
            $so->ext_garansi = [];
        }

        return response()->json([
            'status' => true,
            'data' => $so
        ]);
    }

    /**
     * Update Sales Order
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $date_estimasi = $request->date_estimasi ? date("Y-m-d", strtotime($request->date_estimasi)) : null;
            $ndp_persen = floatval(str_replace(',', '', $request->ndp_persen ?? 0));
            $ndp_amount = floatval(str_replace(',', '', $request->ndp_amount ?? 0));
            $ntenor = intval(str_replace(',', '', $request->ntenor ?? 0));
            $ntenor_amount = floatval(str_replace(',', '', $request->ntenor_amount ?? 0));

            DB::table('tb_so_hdr')->where('id_so', $id)->update([
                'id_karyawan' => $request->id_karyawan,
                'date_estimasi' => $date_estimasi,
                'id_type_pembayaran' => $request->id_type_pembayaran,
                'id_cara_pembayaran' => $request->id_cara_pembayaran,
                'id_waktu_bayar' => $request->id_waktu_bayar,
                'ndp_amount' => $ndp_amount,
                'ndp_persen' => $ndp_persen,
                'ntenor' => $ntenor,
                'ntenor_amount' => $ntenor_amount,
                'customers_address' => $request->customers_address,
                'status_so' => 'DRAFT SALES ORDER',
            ]);

            DB::commit();

            return response()->json([
                'status' => true, 
                'message' => 'Sales Order successfully updated'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to update Sales Order: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Confirm to SO (SALE TO INVOICE) and generate DO
     */
    public function confirm(Request $request)
    {
        $id_so = $request->id_so;
        $so = DB::table('tb_so_hdr')->where('id_so', $id_so)->first();

        if (!$so) {
            return response()->json(['status' => false, 'message' => 'Sales Order not found'], 404);
        }

        DB::beginTransaction();
        try {
            $date_so = $so->date_so;
            $periode = date('Ym');
            $tanggal_sekarang = date("Y-m-d");

            if (date('Y', strtotime($date_so)) == '2010') {
                $periode = date('Ym', strtotime($date_so));
                $tanggal_sekarang = $date_so;
            }

            $delivery_term = $so->delivery_term;
            $f_cif = (strpos($delivery_term, 'CIF') !== false);

            $cek_do = DB::table('tb_do_hdr')->where('id_so', $id_so)->count();

            if ($cek_do == 0 && !$f_cif) {
                // Generate DO Number
                $prefix = "DO-EMM/" . substr($periode, 0, 4) . "/" . substr($periode, 4, 2) . "/";
                $code_do = $prefix . str_pad($this->getRunningNumber('DO', $periode), 5, '0', STR_PAD_LEFT);

                $id_do = DB::table('tb_do_hdr')->insertGetId([
                    'id_so' => $id_so,
                    'code_do' => $code_do,
                    'date_do' => $tanggal_sekarang,
                    'status_do' => 'NEW'
                ]);

                // Insert DO details
                $so_dtl = DB::table('tb_so_dtl')->where('id_so', $id_so)->get();
                foreach ($so_dtl as $dtl) {
                    DB::table('tb_do_dtl')->insert([
                        'id_do' => $id_do,
                        'id_product' => $dtl->id_product,
                        'qty' => $dtl->nqty
                    ]);
                }
            }

            // Update Status SO
            DB::table('tb_so_hdr')->where('id_so', $id_so)->update([
                'status_so' => 'SALE TO INVOICE',
                'date_so' => $tanggal_sekarang
            ]);

            // Change SO Code
            $code_so_lama = $so->code_so;
            $prefix_so = "SO-EMM/" . substr($periode, 0, 4) . "/" . substr($periode, 4, 2) . "/";
            if (date('Y', strtotime($date_so)) == '2010') {
                $code_so_baru = $prefix_so . str_pad($this->getRunningNumber('SO', $periode), 5, '0', STR_PAD_LEFT);
            } else {
                $code_so_baru = $prefix_so . str_pad($this->getRunningNumber('SO BARU', $periode), 5, '0', STR_PAD_LEFT);
            }

            DB::table('tb_so_hdr')->where('id_so', $id_so)->update([
                'code_so' => $code_so_baru
            ]);

            // Save old code to history if needed
            DB::table('tb_so_code_history')->insert([
                'id_so' => $id_so,
                'code_so_old' => $code_so_lama,
                'code_so_new' => $code_so_baru,
                'created_at' => now()
            ]);

            DB::commit();

            return response()->json(['status' => true, 'message' => 'SO confirmed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to confirm SO: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Check Payment -> create Invoice
     */
    public function checkPayment(Request $request)
    {
        $id_so = $request->id_so;
        
        DB::beginTransaction();
        try {
            $cek_invoice = DB::table('tb_invoice_hdr')->where('id_so', $id_so)->first();
            
            if (!$cek_invoice) {
                $so = DB::table('tb_so_hdr')->where('id_so', $id_so)->first();
                $date_invoice = date("Y-m-d", strtotime($request->tgl_status ?? now()));
                $periode = date("Ym", strtotime($date_invoice));
                
                $prefix = "INV-EMM/" . substr($periode, 0, 4) . "/" . substr($periode, 4, 2) . "/";
                $code_invoice = $prefix . str_pad($this->getRunningNumber('INV', $periode), 5, '0', STR_PAD_LEFT);

                $id_invoice = DB::table('tb_invoice_hdr')->insertGetId([
                    'code_invoice' => $code_invoice,
                    'id_so' => $id_so,
                    'id_customers' => $so->id_customers,
                    'date_invoice' => $date_invoice,
                    'flag_ppn' => $so->flag_ppn,
                    'vcurrency' => $so->vcurrency,
                    'ntot_price_netto_amount' => $so->ntot_price_netto_amount,
                    'ntot_balance' => $so->ntot_price_netto_amount,
                    'status_invoice' => 'OPEN'
                ]);
            } else {
                $id_invoice = $cek_invoice->id_invoice;
            }

            DB::table('tb_so_hdr')->where('id_so', $id_so)->update([
                'status_so' => 'SALES ORDER'
            ]);

            DB::commit();

            return response()->json(['status' => true, 'id_invoice' => $id_invoice, 'message' => 'Payment checked successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to check payment: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cancel SO
     */
    public function cancel(Request $request)
    {
        $id_so = $request->id_so;
        $alasan = $request->alasan;
        
        DB::beginTransaction();
        try {
            DB::table('tb_so_hdr')->where('id_so', $id_so)->update([
                'status_so' => 'OUTSTANDING QUOTATION',
            ]);

            // Create Approval for cancellation
            $so = DB::table('tb_so_hdr')->where('id_so', $id_so)->first();
            DB::table('tb_approval')->insert([
                'date_request' => now(),
                'username_request' => $request->username ?? 'system',
                'nm_module' => 'Cancel SO',
                'nm_table' => 'tb_so_hdr',
                'key_table' => 'id_so',
                'id_key_table' => $id_so,
                'code_key_table' => $so->code_so ?? '',
                'status_approve' => '0',
                'alasan' => $alasan
            ]);

            DB::commit();

            return response()->json(['status' => true, 'message' => 'SO canceled and approval requested']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed to cancel SO: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get running number for SO, DO, INV
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
