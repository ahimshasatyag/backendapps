<?php

namespace App\Http\Controllers\customerinvoice;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerInvoiceController extends Controller
{
    /**
     * Get list of Customer Invoices
     * Mirip dengan Mmaster::data() - query tb_invoice_hdr dengan join
     */
    public function index(Request $request)
    {
        $query = DB::table('tb_invoice_hdr as a')
            ->join('m_customers as b', 'a.id_customers', '=', 'b.id_customers')
            ->join('tb_so_hdr as c', function ($join) {
                $join->on('a.id_so', '=', 'c.id_so')
                     ->on('a.id_customers', '=', 'c.id_customers');
            })
            ->join('m_karyawan as d', 'c.id_karyawan', '=', 'd.id_karyawan')
            ->select(
                'a.id_invoice',
                'b.nm_customers',
                'a.date_invoice',
                'a.code_invoice',
                'd.nm_karyawan',
                'c.code_so',
                'a.vcurrency',
                'a.ntot_balance',
                'a.ntot_price_netto_amount',
                'a.status_invoice'
            )
            ->where('a.flag_cancel', '0');

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $terms = explode(',', $search);
                foreach ($terms as $term) {
                    $term = trim($term);
                    $q->orWhere('a.code_invoice', 'like', "%$term%")
                      ->orWhere('b.nm_customers', 'like', "%$term%")
                      ->orWhere('c.code_so', 'like', "%$term%")
                      ->orWhere('a.status_invoice', 'like', "%$term%")
                      ->orWhere('d.nm_karyawan', 'like', "%$term%");
                }
            });
        }

        // Filter by status
        if ($request->has('status_invoice') && !empty($request->status_invoice)) {
            $query->where('a.status_invoice', $request->status_invoice);
        }

        // Filter by date range
        if ($request->has('date_from') && !empty($request->date_from)) {
            $query->where('a.date_invoice', '>=', date('Y-m-d', strtotime($request->date_from)));
        }
        if ($request->has('date_to') && !empty($request->date_to)) {
            $query->where('a.date_invoice', '<=', date('Y-m-d', strtotime($request->date_to)));
        }

        $invoices = $query->orderBy('a.date_invoice', 'desc')
                          ->orderBy('a.id_invoice', 'desc')
                          ->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'data'   => $invoices
        ]);
    }

    /**
     * Get detail of a specific Customer Invoice
     * Mirip dengan Mmaster::data_header() + data_invoice_dtl() + data_barang()
     */
    public function show($id)
    {
        // Header invoice
        $invoice = DB::table('tb_invoice_hdr as a')
            ->join('m_customers as b', 'a.id_customers', '=', 'b.id_customers')
            ->join('tb_so_hdr as c', function ($join) {
                $join->on('a.id_so', '=', 'c.id_so')
                     ->on('a.id_customers', '=', 'c.id_customers');
            })
            ->select(
                'a.*',
                'b.nm_customers',
                'b.customers_address',
                'b.customers_address_invoice',
                'b.customers_phone',
                'c.code_so',
                'c.nkurs',
                'c.ndp_persen',
                'c.ndp_amount',
                'c.date_so',
                'c.nppn_amount',
                'c.no_po_cust'
            )
            ->where('a.id_invoice', $id)
            ->first();

        if (!$invoice) {
            return response()->json(['status' => false, 'message' => 'Invoice not found'], 404);
        }

        // Detail pembayaran (tb_invoice_dtl)
        $invoice_dtl = DB::table('tb_invoice_dtl as a')
            ->join('m_payment_method_hdr as b', 'a.id_payment_method', '=', 'b.id_payment_method')
            ->leftJoin('tb_retur_penjualan_hdr as c', 'a.retur_penjualan_id', '=', 'c.id')
            ->select(
                'a.*',
                'b.nm_payment_method',
                'c.code as code_retur'
            )
            ->where('a.id_invoice', $id)
            ->where('a.f_cancel', '0')
            ->get();

        // Detail barang dari SO
        $barang = DB::table('tb_so_dtl as a')
            ->join('m_product as b', 'a.id_product', '=', 'b.id_product')
            ->join('m_product_satuan as c', 'b.id_product_satuan', '=', 'c.id_product_satuan')
            ->join('m_product_price as d', 'a.id_product', '=', 'd.id_product')
            ->join('m_product_brand as e', 'b.id_product_brand', '=', 'e.id_product_brand')
            ->select(
                'a.id_product',
                'b.code_product',
                'b.nm_product',
                'b.product_deskripsi',
                'a.product_price',
                'a.nqty',
                'c.nm_product_satuan',
                'd.delivery_term',
                'a.ntax',
                'e.nm_product_brand'
            )
            ->where('a.id_so', $invoice->id_so)
            ->get();

        $invoice->invoice_dtl  = $invoice_dtl;
        $invoice->barang        = $barang;

        return response()->json([
            'status' => true,
            'data'   => $invoice
        ]);
    }

    /**
     * Simpan detail invoice / payment schedule
     * Mirip dengan Cform::simpan_detatil_invoice()
     */
    public function storeInvoiceDetail(Request $request)
    {
        $id_invoice        = $request->id_invoice;
        $id_invoice_dtl    = $request->id_invoice_dtl;
        $id_payment_method = $request->id_payment_method;
        $date_payment      = $request->date_payment ? date('Y-m-d', strtotime($request->date_payment)) : null;
        $id_bank           = $request->id_bank;
        $payment_ref       = $request->payment_ref;
        $nkurs             = str_replace(',', '', $request->nkurs ?? '0');
        $dp                = $request->dp;
        $v_amount          = str_replace(',', '', $request->v_amount ?? '0');
        $code_giro         = null;
        $retur_penjualan_id = null;
        $id_kb_masuk       = null;

        if ($id_payment_method == 6) {
            $id_kb_masuk = $request->id_kb_masuk;
        }

        if ($id_payment_method == 2) {
            $code_giro = $request->code_giro;
        }

        if ($id_payment_method == 4) {
            $retur_penjualan_id = $request->retur_penjualan_id;
        }

        DB::beginTransaction();
        try {
            if ($id_invoice_dtl) {
                // Update existing
                DB::table('tb_invoice_dtl')
                    ->where('id_invoice', $id_invoice)
                    ->where('id_invoice_dtl', $id_invoice_dtl)
                    ->update([
                        'id_payment_method'  => $id_payment_method,
                        'id_bank'            => $id_bank,
                        'payment_ref'        => $payment_ref,
                        'v_amount'           => $v_amount,
                        'nkurs'              => $nkurs,
                        'code_giro'          => $code_giro,
                        'date_draft'         => $date_payment,
                        'f_dp'               => $dp,
                        'retur_penjualan_id' => $retur_penjualan_id,
                        'id_kb_masuk'        => $id_kb_masuk,
                    ]);
            } else {
                // Insert new
                DB::table('tb_invoice_dtl')->insert([
                    'id_invoice'         => $id_invoice,
                    'id_payment_method'  => $id_payment_method,
                    'id_bank'            => $id_bank,
                    'payment_ref'        => $payment_ref,
                    'v_amount'           => $v_amount,
                    'nkurs'              => $nkurs,
                    'code_giro'          => $code_giro,
                    'date_draft'         => $date_payment,
                    'f_dp'               => $dp,
                    'status_payment'     => 'DRAFT',
                    'retur_penjualan_id' => $retur_penjualan_id,
                    'id_kb_masuk'        => $id_kb_masuk,
                ]);
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Invoice detail saved successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Ganti status pembayaran invoice detail
     * Mirip dengan Cform::ganti_status()
     */
    public function gantiStatus(Request $request)
    {
        $id_invoice_dtl = $request->id_invoice_dtl;
        $status_payment = $request->status;
        $tgl_status     = $request->tgl_status;
        $alasan         = $request->alasan;

        DB::beginTransaction();
        try {
            if ($status_payment === 'TERIMA') {
                DB::table('tb_invoice_dtl')
                    ->where('id_invoice_dtl', $id_invoice_dtl)
                    ->update([
                        'status_payment' => 'TERIMA',
                        'date_terima'    => date('Y-m-d', strtotime($tgl_status)),
                        'date_update'    => now(),
                    ]);
                DB::commit();
                return response()->json(['status' => true, 'message' => 'Status updated to TERIMA']);

            } elseif ($status_payment === 'SETOR') {
                DB::table('tb_invoice_dtl')
                    ->where('id_invoice_dtl', $id_invoice_dtl)
                    ->update([
                        'status_payment' => 'SETOR',
                        'date_setor'     => date('Y-m-d', strtotime($tgl_status)),
                        'date_update'    => now(),
                    ]);
                DB::commit();
                return response()->json(['status' => true, 'message' => 'Status updated to SETOR']);

            } elseif ($status_payment === 'CAIR') {
                $data_payment = DB::table('tb_invoice_dtl')
                    ->where('id_invoice_dtl', $id_invoice_dtl)
                    ->first();

                if (!$data_payment) {
                    DB::rollBack();
                    return response()->json(['status' => false, 'message' => 'Payment detail not found'], 404);
                }

                $id_invoice   = $data_payment->id_invoice;
                $data_invoice = DB::table('tb_invoice_hdr')->where('id_invoice', $id_invoice)->first();

                $v_amount_schdl       = $data_payment->v_amount;
                $ntot_balance_invoice = $data_invoice->ntot_balance;

                if ($ntot_balance_invoice > 0 && ($ntot_balance_invoice - $v_amount_schdl) >= 0) {

                    // Kurangi balance invoice
                    DB::table('tb_invoice_hdr')
                        ->where('id_invoice', $id_invoice)
                        ->update([
                            'ntot_balance' => $ntot_balance_invoice - $v_amount_schdl
                        ]);

                    if ($data_payment->id_payment_method != 2) {
                        DB::table('tb_invoice_dtl')
                            ->where('id_invoice_dtl', $id_invoice_dtl)
                            ->update([
                                'date_terima' => date('Y-m-d', strtotime($tgl_status)),
                                'date_setor'  => date('Y-m-d', strtotime($tgl_status)),
                                'date_update' => now(),
                            ]);
                    }

                    DB::table('tb_invoice_dtl')
                        ->where('id_invoice_dtl', $id_invoice_dtl)
                        ->update([
                            'status_payment' => 'CAIR',
                            'date_cair'      => date('Y-m-d', strtotime($tgl_status)),
                            'date_update'    => now(),
                        ]);

                    // Jika balance habis, close invoice
                    if (($ntot_balance_invoice - $v_amount_schdl) == 0) {
                        DB::table('tb_invoice_hdr')
                            ->where('id_invoice', $id_invoice)
                            ->update(['status_invoice' => 'CLOSE']);
                    }

                    // Kurangi sisa retur jika metode pembayaran retur
                    if ($data_payment->id_payment_method == 4) {
                        $retur = DB::table('tb_retur_penjualan_hdr')
                            ->where('id', $data_payment->retur_penjualan_id)
                            ->first();
                        if ($retur) {
                            DB::table('tb_retur_penjualan_hdr')
                                ->where('id', $data_payment->retur_penjualan_id)
                                ->update(['sisa' => $retur->sisa - $v_amount_schdl]);
                        }
                    }

                    // Kurangi sisa kas bank masuk jika metode KasBank
                    if ($data_payment->id_payment_method == 6) {
                        $kb = DB::table('tb_kb_masuk_hdr')
                            ->where('id_kb_masuk', $data_payment->id_kb_masuk)
                            ->first();
                        if ($kb) {
                            DB::table('tb_kb_masuk_hdr')
                                ->where('id_kb_masuk', $data_payment->id_kb_masuk)
                                ->update(['v_balance' => $kb->v_balance - $v_amount_schdl]);
                        }
                    }

                    DB::commit();
                    return response()->json(['status' => true, 'message' => 'Status updated to CAIR']);
                } else {
                    DB::rollBack();
                    return response()->json(['status' => false, 'message' => 'Jumlah pembayaran melebihi sisa tagihan']);
                }

            } elseif ($status_payment === 'TOLAK') {
                $updateData = [
                    'status_payment' => 'TOLAK',
                    'date_tolak'     => date('Y-m-d', strtotime($tgl_status)),
                    'date_update'    => now(),
                ];
                if ($alasan) {
                    $updateData['alasan_tolak_cancel'] = $alasan;
                }
                DB::table('tb_invoice_dtl')
                    ->where('id_invoice_dtl', $id_invoice_dtl)
                    ->update($updateData);
                DB::commit();
                return response()->json(['status' => true, 'message' => 'Status updated to TOLAK']);

            } elseif ($status_payment === 'BATAL') {
                $updateData = [
                    'f_cancel'       => '1',
                    'status_payment' => 'BATAL',
                    'date_cancel'    => date('Y-m-d', strtotime($tgl_status)),
                    'date_update'    => now(),
                ];
                if ($alasan) {
                    $updateData['alasan_tolak_cancel'] = $alasan;
                }
                DB::table('tb_invoice_dtl')
                    ->where('id_invoice_dtl', $id_invoice_dtl)
                    ->update($updateData);
                DB::commit();
                return response()->json(['status' => true, 'message' => 'Status updated to BATAL']);
            }

            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Status tidak dikenal'], 400);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Back status ke status sebelumnya
     * Mirip dengan Cform::back_status()
     */
    public function backStatus(Request $request)
    {
        $id_invoice     = $request->id_invoice;
        $id_invoice_dtl = $request->id_invoice_dtl;

        $data_invoice_dtl = DB::table('tb_invoice_dtl')
            ->where('id_invoice_dtl', $id_invoice_dtl)
            ->first();

        if (!$data_invoice_dtl) {
            return response()->json(['status' => false, 'message' => 'Invoice detail not found'], 404);
        }

        DB::beginTransaction();
        try {
            if ($data_invoice_dtl->status_payment === 'TERIMA') {
                DB::table('tb_invoice_dtl')
                    ->where('id_invoice_dtl', $id_invoice_dtl)
                    ->update(['status_payment' => 'DRAFT', 'date_update' => now()]);
                DB::commit();
                return response()->json(['status' => true, 'message' => 'Berhasil back status menjadi DRAFT']);

            } elseif ($data_invoice_dtl->status_payment === 'SETOR') {
                DB::table('tb_invoice_dtl')
                    ->where('id_invoice_dtl', $id_invoice_dtl)
                    ->update(['status_payment' => 'TERIMA', 'date_update' => now()]);
                DB::commit();
                return response()->json(['status' => true, 'message' => 'Berhasil back status menjadi TERIMA']);

            } elseif ($data_invoice_dtl->status_payment === 'CAIR') {
                // Tentukan id_approve berdasarkan metode pembayaran
                if ($data_invoice_dtl->id_payment_method == 2) {
                    $id_approve = 8;
                } elseif ($data_invoice_dtl->id_payment_method == 4) {
                    $id_approve = 10;
                } else {
                    $id_approve = 9;
                }

                $cek_data = DB::table('tb_approval')
                    ->where('id_approve', $id_approve)
                    ->where('id_key_table', $id_invoice_dtl)
                    ->where('status_approve', 0)
                    ->count();

                if ($cek_data == 0) {
                    // Buat permintaan approval
                    $approve_hdr = DB::table('m_approve_hdr')->where('id_approve', $id_approve)->first();
                    $code_invoice = DB::table('tb_invoice_hdr')
                        ->where('id_invoice', $id_invoice)
                        ->value('code_invoice');

                    if ($approve_hdr) {
                        DB::table('tb_approval')->insert([
                            'id_approve'       => $approve_hdr->id_approve,
                            'date_request'     => now(),
                            'username_request' => $request->username ?? auth()->user()->username ?? 'system',
                            'nm_module'        => $approve_hdr->nm_module,
                            'nm_table'         => $approve_hdr->nm_table,
                            'key_table'        => $approve_hdr->key_table,
                            'id_key_table'     => $id_invoice_dtl,
                            'code_key_table'   => $code_invoice,
                            'status_table'     => $approve_hdr->status_table,
                            'action_approve'   => $approve_hdr->action_approve,
                            'action_canceled'  => $approve_hdr->action_canceled,
                            'status_approve'   => '0',
                            'alasan'           => '',
                            'id_menu'          => '11101',
                        ]);
                    }
                }

                DB::commit();
                return response()->json(['status' => true, 'message' => 'Silahkan tunggu approve ya :)']);

            } elseif ($data_invoice_dtl->status_payment === 'TOLAK') {
                DB::table('tb_invoice_dtl')
                    ->where('id_invoice_dtl', $id_invoice_dtl)
                    ->update(['status_payment' => 'DRAFT', 'date_update' => now()]);
                DB::commit();
                return response()->json(['status' => true, 'message' => 'Berhasil back status menjadi DRAFT']);
            }

            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Status tidak dapat di-back'], 400);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Posting AR Pelunasan
     * Mirip dengan Cform::posting()
     */
    public function posting(Request $request)
    {
        $id_invoice     = $request->id_invoice;
        $id_invoice_dtl = $request->id_invoice_dtl;
        $id_customers   = $request->id_customers;

        DB::beginTransaction();
        try {
            $invoice_balance = DB::table('tb_invoice_dtl')
                ->where('id_invoice', $id_invoice)
                ->where('id_invoice_dtl', $id_invoice_dtl)
                ->value('invoice_balance');

            $ntot_balance = DB::table('tb_invoice_hdr')
                ->where('id_invoice', $id_invoice)
                ->where('id_customers', $id_customers)
                ->value('ntot_balance');

            // Update ar_pelunasan_hdr: set balance to 0, flag_posting = true
            DB::table('tb_ar_pelunasan_hdr')
                ->where('id_invoice', $id_invoice)
                ->where('id_invoice_dtl', $id_invoice_dtl)
                ->where('id_customers', $id_customers)
                ->update([
                    'balance_amount' => 0,
                    'flag_posting'   => true,
                ]);

            // Update invoice_dtl: set invoice_balance to 0
            DB::table('tb_invoice_dtl')
                ->where('id_invoice', $id_invoice)
                ->where('id_invoice_dtl', $id_invoice_dtl)
                ->update(['invoice_balance' => 0]);

            // Update invoice_hdr: kurangi ntot_balance
            DB::table('tb_invoice_hdr')
                ->where('id_invoice', $id_invoice)
                ->where('id_customers', $id_customers)
                ->update(['ntot_balance' => $ntot_balance - $invoice_balance]);

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Posting berhasil']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Unposting AR Pelunasan
     * Mirip dengan Cform::unposting()
     */
    public function unposting(Request $request)
    {
        $id_invoice     = $request->id_invoice;
        $id_invoice_dtl = $request->id_invoice_dtl;
        $id_customers   = $request->id_customers;

        DB::beginTransaction();
        try {
            $invoice_amount = DB::table('tb_invoice_dtl')
                ->where('id_invoice', $id_invoice)
                ->where('id_invoice_dtl', $id_invoice_dtl)
                ->value('invoice_amount');

            $ntot_balance = DB::table('tb_invoice_hdr')
                ->where('id_invoice', $id_invoice)
                ->where('id_customers', $id_customers)
                ->value('ntot_balance');

            // Kembalikan balance_amount, set flag_posting = false
            DB::table('tb_ar_pelunasan_hdr')
                ->where('id_invoice', $id_invoice)
                ->where('id_invoice_dtl', $id_invoice_dtl)
                ->where('id_customers', $id_customers)
                ->update([
                    'balance_amount' => $invoice_amount,
                    'flag_posting'   => false,
                ]);

            // Kembalikan invoice_balance
            DB::table('tb_invoice_dtl')
                ->where('id_invoice', $id_invoice)
                ->where('id_invoice_dtl', $id_invoice_dtl)
                ->update(['invoice_balance' => $invoice_amount]);

            // Tambahkan kembali ntot_balance
            DB::table('tb_invoice_hdr')
                ->where('id_invoice', $id_invoice)
                ->where('id_customers', $id_customers)
                ->update(['ntot_balance' => $ntot_balance + $invoice_amount]);

            DB::commit();
            return response()->json(['status' => true, 'message' => 'Unposting berhasil']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Simpan AR Pelunasan (header + detail)
     * Mirip dengan Cform::simpan_ar_pelunasan()
     */
    public function simpanArPelunasan(Request $request)
    {
        $id_invoice       = $request->id_invoice;
        $id_customers     = $request->id_customers;
        $id_invoice_dtl   = $request->id_invoice_dtl;
        $date_ar_pelunasan = date('Y-m-d', strtotime($request->date_ar_pelunasan));
        $periode           = date('Ym', strtotime($date_ar_pelunasan));
        $vpaid_amount      = $request->vpaid_amount;
        $memo              = trim($request->memo ?? '');
        $data_dtl          = $request->data_dtl ?? [];

        DB::beginTransaction();
        try {
            // Generate code AR Pelunasan
            $code_ar_pelunasan = $this->generateRunningNumber('PL', $periode);

            $id_ar_pelunasan = DB::table('tb_ar_pelunasan_hdr')->insertGetId([
                'code_ar_pelunasan' => $code_ar_pelunasan,
                'id_invoice'        => $id_invoice,
                'id_invoice_dtl'    => $id_invoice_dtl,
                'id_customers'      => $id_customers,
                'date_ar_pelunasan' => $date_ar_pelunasan,
                'vpaid_amount'      => $vpaid_amount,
                'balance_amount'    => $vpaid_amount,
                'memo'              => $memo,
                'flag_posting'      => false,
            ]);

            if ($id_ar_pelunasan && is_array($data_dtl)) {
                foreach ($data_dtl as $row) {
                    DB::table('tb_ar_pelunasan_dtl')->insert([
                        'id_ar_pelunasan'  => $id_ar_pelunasan,
                        'id_payment_method' => $row['id_payment_method'],
                        'id_payment_ref'   => $row['id_payment_ref'],
                        'vpaid_amount'     => $row['vpaid_amount'],
                    ]);
                }
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'AR Pelunasan berhasil disimpan', 'id_ar_pelunasan' => $id_ar_pelunasan]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get support data (payment method, bank, retur, kb masuk)
     * Mirip dengan Cform::modal_register_payment()
     */
    public function supportData(Request $request)
    {
        $id_invoice = $request->id_invoice;

        $invoice = DB::table('tb_invoice_hdr')->where('id_invoice', $id_invoice)->first();

        $payment_methods = DB::table('m_payment_method_hdr')->get()->map(function ($item) {
            return [
                'label' => $item->nm_payment_method,
                'value' => (string)$item->id_payment_method
            ];
        });

        $banks = DB::table('m_bank')->get()->map(function ($item) {
            return [
                'label' => $item->nm_bank . ' - ' . $item->nm_rekening . ' (' . $item->no_rekening . ')',
                'value' => (string)$item->id_bank
            ];
        });

        $retur = [];
        $kb_masuk = [];

        if ($invoice) {
            $retur = DB::table('tb_retur_penjualan_hdr')
                ->where('id_so', $invoice->id_so)
                ->where('flag_cancel', '0')
                ->where('sisa', '>', '0')
                ->where('status_retur', 'RETUR')
                ->get()
                ->map(function ($item) {
                    return [
                        'label' => $item->code . ' (Sisa: ' . number_format($item->sisa, 0, ',', '.') . ')',
                        'value' => (string)$item->id
                    ];
                });

            $kb_masuk = DB::table('tb_kb_masuk_hdr')
                ->where('id_so', $invoice->id_so)
                ->where('f_dp', '1')
                ->where('v_balance', '>', 0)
                ->get()
                ->map(function ($item) {
                    return [
                        'label' => $item->code_kb_masuk . ' (Balance: ' . number_format($item->v_balance, 0, ',', '.') . ')',
                        'value' => (string)$item->id_kb_masuk
                    ];
                });
        }

        return response()->json([
            'status'          => true,
            'payment_methods' => $payment_methods,
            'banks'           => $banks,
            'retur'           => $retur,
            'kb_masuk'        => $kb_masuk,
        ]);
    }

    /**
     * Get data giro untuk invoice
     * Mirip dengan Cform::get_data_giro()
     */
    public function getDataGiro(Request $request)
    {
        $id_invoice   = $request->id_invoice;
        $id_customers = $request->id_customers;
        $id_giro      = $request->id_giro;

        $query = DB::table('tb_giro')
            ->where('id_invoice', $id_invoice)
            ->where('id_customers', $id_customers)
            ->whereNull('date_cair');

        if ($id_giro) {
            $query->where('id_giro', $id_giro);
        }

        $data_giro = $query->first();

        $max_paid = 0;
        $memo     = '';

        if ($data_giro) {
            $max_paid = $data_giro->amount_giro;
            $memo     = $data_giro->keterangan;
        }

        return response()->json(['status' => true, 'max_paid' => $max_paid, 'memo' => $memo]);
    }

    /**
     * Get AR Pelunasan header & detail per invoice dtl
     * Mirip dengan Cform - data_ar_pelunasan_hdr + data_ar_pelunasan_dtl
     */
    public function getArPelunasan(Request $request)
    {
        $id_invoice     = $request->id_invoice;
        $id_invoice_dtl = $request->id_invoice_dtl;

        $pelunasan_hdr = DB::table('tb_ar_pelunasan_hdr')
            ->where('id_invoice', $id_invoice)
            ->where('id_invoice_dtl', $id_invoice_dtl)
            ->get();

        $result = [];
        foreach ($pelunasan_hdr as $hdr) {
            $hdr->detail = DB::table('tb_ar_pelunasan_dtl')
                ->where('id_ar_pelunasan', $hdr->id_ar_pelunasan)
                ->get();
            $result[] = $hdr;
        }

        return response()->json(['status' => true, 'data' => $result]);
    }

    /**
     * Get retur data berdasarkan id_invoice
     * Mirip dengan Cform::get_retur()
     */
    public function getRetur(Request $request)
    {
        $id_invoice = $request->id_invoice;

        $invoice = DB::table('tb_invoice_hdr')->where('id_invoice', $id_invoice)->first();

        if (!$invoice) {
            return response()->json(['status' => false, 'message' => 'Invoice not found'], 404);
        }

        $retur = DB::table('tb_retur_penjualan_hdr')
            ->where('id_so', $invoice->id_so)
            ->where('flag_cancel', '0')
            ->where('sisa', '>', '0')
            ->where('status_retur', 'RETUR')
            ->get();

        return response()->json(['status' => true, 'data' => $retur]);
    }

    /**
     * Get kas bank masuk berdasarkan id_invoice
     * Mirip dengan Cform::get_kb_masuk_hdr()
     */
    public function getKbMasuk(Request $request)
    {
        $id_invoice = $request->id_invoice;

        $invoice = DB::table('tb_invoice_hdr')->where('id_invoice', $id_invoice)->first();

        if (!$invoice) {
            return response()->json(['status' => false, 'message' => 'Invoice not found'], 404);
        }

        $kb_masuk = DB::table('tb_kb_masuk_hdr')
            ->where('id_so', $invoice->id_so)
            ->where('f_dp', '1')
            ->where('v_balance', '>', 0)
            ->get();

        return response()->json(['status' => true, 'data' => $kb_masuk]);
    }

    /**
     * Update code PI (Proforma Invoice)
     * Mirip dengan Mmaster::update_code_pi()
     */
    public function updateCodePi(Request $request)
    {
        $id_invoice = $request->id_invoice;

        $invoice = DB::table('tb_invoice_hdr')->where('id_invoice', $id_invoice)->first();

        if (!$invoice) {
            return response()->json(['status' => false, 'message' => 'Invoice not found'], 404);
        }

        if (empty($invoice->code_pi)) {
            $code_pi = $this->generateRunningNumberBulan('PI');
            DB::table('tb_invoice_hdr')
                ->where('id_invoice', $id_invoice)
                ->update(['code_pi' => $code_pi, 'date_pi' => now()]);
        }

        $invoice = DB::table('tb_invoice_hdr')->where('id_invoice', $id_invoice)->first();

        return response()->json(['status' => true, 'data' => $invoice]);
    }

    /**
     * Generate running number per periode (tahun)
     * Mirip dengan CI runningnumber_tahun()
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

    /**
     * Generate running number per bulan
     * Mirip dengan CI runningnumber_bulan()
     */
    private function generateRunningNumberBulan($prefix)
    {
        $periode = date('Ym');
        $year    = date('Y');
        $month   = date('m');

        $cek = DB::table('m_counter')
            ->where('id_counter', $prefix)
            ->where('periode', $periode)
            ->first();

        if ($cek) {
            $no = $cek->no_counter + 1;
            DB::table('m_counter')
                ->where('id_counter', $prefix)
                ->where('periode', $periode)
                ->update(['no_counter' => $no]);
        } else {
            $no = 1;
            DB::table('m_counter')->insert([
                'id_counter' => $prefix,
                'periode'    => $periode,
                'no_counter' => $no,
            ]);
        }

        return $prefix . '-EMM/' . $year . '/' . $month . '/' . str_pad($no, 5, '0', STR_PAD_LEFT);
    }
}
