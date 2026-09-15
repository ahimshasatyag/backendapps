<?php

namespace App\Http\Controllers\payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * List Payments
     */
    public function index(Request $request)
    {
        $query = DB::table('tb_payment_schdl as a')
            ->join('m_customers as b', 'a.id_customers', '=', 'b.id_customers')
            ->join('tb_invoice_hdr as c', function($join) {
                $join->on('a.id_invoice', '=', 'c.id_invoice')
                     ->on('c.id_customers', '=', 'b.id_customers');
            })
            ->join('tb_so_hdr as d', 'c.id_so', '=', 'd.id_so')
            ->where('a.code_payment_schdl', '!=', '')
            ->where('a.f_cancel', '0')
            ->select(
                'a.id_payment_schdl',
                'a.date_update',
                'c.vcurrency',
                'b.nm_customers',
                'a.code_payment_schdl',
                'a.no_giro',
                'a.date_payment',
                'a.v_amount',
                'a.status_payment',
                'd.code_so'
            );

        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $terms = explode(',', $search);
                foreach ($terms as $term) {
                    $term = trim($term);
                    $q->orWhere('a.code_payment_schdl', 'like', "%$term%")
                      ->orWhere('b.nm_customers', 'like', "%$term%")
                      ->orWhere('d.code_so', 'like', "%$term%")
                      ->orWhere('a.status_payment', 'like', "%$term%");
                }
            });
        }

        $data = $query->orderBy('a.date_update', 'desc')->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'data'   => $data
        ]);
    }

    /**
     * Support Data for Dropdowns
     */
    public function supportData()
    {
        $data_customers_invoice = DB::table('tb_invoice_hdr as a')
            ->join('m_customers as b', 'a.id_customers', '=', 'b.id_customers')
            ->where('a.ntot_balance', '>', 0)
            ->select('a.id_customers', 'b.nm_customers')
            ->groupBy('a.id_customers', 'b.nm_customers')
            ->get();

        return response()->json([
            'status' => true,
            'data_invoice' => DB::table('tb_invoice_hdr')->where('ntot_balance', '>', 0)->get(),
            'data_customers_invoice' => $data_customers_invoice,
            'data_payment_method' => DB::table('m_payment_method_hdr')->get(),
            'data_bank' => DB::table('m_bank')->get(),
            'data_bank_giro' => DB::table('m_bank_giro')->get()
        ]);
    }

    /**
     * Get Invoice List by Customer
     */
    public function getInvoiceByCustomer($id_customers)
    {
        $data = DB::table('tb_invoice_hdr')
            ->where('id_customers', $id_customers)
            ->select('id_invoice', 'code_invoice')
            ->get();
            
        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * Get Customer detail by Invoice
     */
    public function getCustomerByInvoice($id_invoice)
    {
        $data = DB::table('tb_invoice_hdr as a')
            ->join('m_customers as b', 'a.id_customers', '=', 'b.id_customers')
            ->join('tb_so_hdr as c', function($join) {
                $join->on('a.id_so', '=', 'c.id_so')
                     ->on('a.id_customers', '=', 'c.id_customers')
                     ->on('b.id_customers', '=', 'c.id_customers');
            })
            ->where('a.id_invoice', $id_invoice)
            ->select(
                'a.code_invoice',
                'a.id_customers',
                'b.nm_customers',
                'c.ntenor',
                'c.ndp_amount',
                'c.ntenor_amount',
                'c.ntot_price_netto_amount',
                'a.ntot_balance',
                'a.vcurrency',
                'c.nkurs'
            )
            ->get();
            
        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * Store Data
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $id_invoice = $request->id_invoice;
            $id_customers = $request->id_customers;
            $id_bank = $request->id_bank;
            
            $data_invoice = DB::table('tb_invoice_hdr')->where('id_invoice', $id_invoice)->first();
            if (!$data_invoice) {
                return response()->json(['status' => false, 'message' => 'Invoice not found'], 404);
            }
            
            $code_invoice = $data_invoice->code_invoice;
            
            $x_dp = 1;
            $x_cicilan = 1;
            
            $payments = $request->payments; // Expect array of payment objects from frontend
            if ($payments && is_array($payments)) {
                foreach ($payments as $pay) {
                    $id_payment_method = $pay['id_payment_method'] ?? null;
                    if ($id_payment_method) {
                        $date_payment = date("Y-m-d", strtotime($pay['date_payment']));
                        $v_amount = str_replace(',', '', $pay['v_amount']);
                        $payment_ref = $pay['payment_ref'] ?? null;
                        
                        // Parse boolean or 'on' for DP
                        $dp = !empty($pay['dp']) && ($pay['dp'] === 'on' || $pay['dp'] === true || $pay['dp'] === 1 || $pay['dp'] === '1');
                        
                        $no_giro = null;
                        $bank_giro_id = null;
                        if ($id_payment_method == 2 || $id_payment_method == '2') {
                            $no_giro = $pay['no_giro'] ?? null;
                            $bank_giro_id = $pay['bank_giro_id'] ?? null;
                        }
                        
                        $nkurs = null;
                        if (!empty($pay['nkurs'])) {
                            $nkurs = str_replace(',', '', $pay['nkurs']);
                        }
                        
                        if ($dp) {
                            $code_payment_schdl = $code_invoice . ".0-" . $x_dp;
                            $x_dp++;
                        } else {
                            $code_payment_schdl = $code_invoice . "." . $x_cicilan;
                            $x_cicilan++;
                        }
                        
                        DB::table('tb_payment_schdl')->insert([
                            'id_invoice' => $id_invoice,
                            'id_customers' => $id_customers,
                            'id_payment_method' => $id_payment_method,
                            'date_payment' => $date_payment,
                            'v_amount' => $v_amount,
                            'payment_ref' => $payment_ref,
                            'status_payment' => 'DRAFT',
                            'date_create' => now(),
                            'date_update' => now(),
                            'no_giro' => $no_giro,
                            'bank_giro_id' => $bank_giro_id,
                            'id_bank' => $id_bank,
                            'code_payment_schdl' => $code_payment_schdl,
                            'nkurs' => $nkurs,
                            'f_dp' => $dp ? 1 : 0
                        ]);
                    }
                }
            }
            
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Data payment berhasil disimpan']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Change Status
     */
    public function changeStatus(Request $request)
    {
        DB::beginTransaction();
        try {
            $data_id_payment_schdl = $request->id_payment_schdl;
            if (is_string($data_id_payment_schdl)) {
                $data_id_payment_schdl = explode("|", $data_id_payment_schdl);
            }
            
            $status_payment = $request->status;
            $tgl_status = date('Y-m-d', strtotime($request->tgl_status));
            $alasan = $request->alasan ?? null;
            
            foreach ($data_id_payment_schdl as $id_payment_schdl) {
                if ($id_payment_schdl != '') {
                    $updateData = [
                        'status_payment' => $status_payment,
                        'date_update' => now()
                    ];
                    
                    if ($alasan != null) {
                        $updateData['alasan_tolak_cancel'] = $alasan;
                    }
                    
                    if ($status_payment == 'TERIMA') {
                        $updateData['date_terima'] = $tgl_status;
                        DB::table('tb_payment_schdl')->where('id_payment_schdl', $id_payment_schdl)->update($updateData);
                        
                    } elseif ($status_payment == 'SETOR') {
                        $updateData['date_setor'] = $tgl_status;
                        DB::table('tb_payment_schdl')->where('id_payment_schdl', $id_payment_schdl)->update($updateData);
                        
                    } elseif ($status_payment == 'CAIR') {
                        $data_payment_schdl = DB::table('tb_payment_schdl')->where('id_payment_schdl', $id_payment_schdl)->first();
                        $id_invoice = $data_payment_schdl->id_invoice;
                        $data_invoice = DB::table('tb_invoice_hdr')->where('id_invoice', $id_invoice)->first();
                        
                        $v_amount_schdl = $data_payment_schdl->v_amount;
                        $ntot_balance_invoice = $data_invoice->ntot_balance;
                        
                        if ($ntot_balance_invoice > 0 && (($ntot_balance_invoice - $v_amount_schdl) >= 0)) {
                            // Kurangi balance
                            DB::table('tb_invoice_hdr')
                                ->where('id_invoice', $id_invoice)
                                ->decrement('ntot_balance', $v_amount_schdl);
                            
                            $updateData['date_cair'] = $tgl_status;
                            DB::table('tb_payment_schdl')->where('id_payment_schdl', $id_payment_schdl)->update($updateData);
                            
                            if (($ntot_balance_invoice - $v_amount_schdl) == 0) {
                                DB::table('tb_invoice_hdr')->where('id_invoice', $id_invoice)->update(['status_invoice' => 'CLOSE']);
                            }
                        }
                        
                    } elseif ($status_payment == 'TOLAK') {
                        $updateData['date_tolak'] = $tgl_status;
                        DB::table('tb_payment_schdl')->where('id_payment_schdl', $id_payment_schdl)->update($updateData);
                        
                    } elseif ($status_payment == 'BATAL') {
                        $updateData['date_cancel'] = $tgl_status;
                        $updateData['f_cancel'] = '1';
                        DB::table('tb_payment_schdl')->where('id_payment_schdl', $id_payment_schdl)->update($updateData);
                    }
                }
            }
            
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Status berhasil diubah']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Show Data for Edit
     */
    public function show($id)
    {
        $data = DB::table('tb_payment_schdl')->where('id_payment_schdl', $id)->first();
        if (!$data) {
            return response()->json(['status' => false, 'message' => 'Data not found'], 404);
        }
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Update Data
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $id_payment_method = $request->id_payment_method;
            $date_payment = date("Y-m-d", strtotime($request->date_payment));
            $v_amount = str_replace(',', '', $request->v_amount);
            $payment_ref = $request->payment_ref;
            
            $dp = !empty($request->dp) && ($request->dp === 'on' || $request->dp === true || $request->dp === 1 || $request->dp === '1');
            
            $nkurs = !empty($request->nkurs) ? str_replace(',', '', $request->nkurs) : null;
            
            $no_giro = null;
            $bank_giro_id = null;
            if ($id_payment_method == 2 || $id_payment_method == '2') {
                $no_giro = $request->no_giro;
                $bank_giro_id = $request->bank_giro_id;
            }
            
            DB::table('tb_payment_schdl')->where('id_payment_schdl', $id)->update([
                'id_payment_method' => $id_payment_method,
                'date_payment' => $date_payment,
                'v_amount' => $v_amount,
                'payment_ref' => $payment_ref,
                'date_update' => now(),
                'no_giro' => $no_giro,
                'bank_giro_id' => $bank_giro_id,
                'nkurs' => $nkurs,
                'f_dp' => $dp ? 1 : 0
            ]);
            
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Data berhasil diupdate']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete (Cancel) Payment
     */
    public function cancel($id)
    {
        DB::beginTransaction();
        try {
            DB::table('tb_payment_schdl')->where('id_payment_schdl', $id)->update([
                'f_cancel' => '1',
                'status_payment' => 'BATAL',
                'date_update' => now()
            ]);
            
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Payment cancelled']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Split Payment
     */
    public function split(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $id_invoice = $request->id_invoice;
            $id_customers = $request->id_customers;
            $id_bank = $request->id_bank;
            
            $data_payment = DB::table('tb_payment_schdl')->where('id_payment_schdl', $id)->first();
            if (!$data_payment) {
                return response()->json(['status' => false, 'message' => 'Payment schedule not found'], 404);
            }
            
            $code_invoice = $data_payment->code_payment_schdl;
            
            // Delete old schedule & mark BATAL
            DB::table('tb_payment_schdl')->where('id_payment_schdl', $id)->update([
                'f_cancel' => '1',
                'status_payment' => 'BATAL',
                'date_update' => now()
            ]);
            
            $payments = $request->payments;
            if ($payments && is_array($payments)) {
                $i = 1;
                foreach ($payments as $pay) {
                    $id_payment_method = $pay['id_payment_method'] ?? null;
                    if ($id_payment_method) {
                        $date_payment = date("Y-m-d", strtotime($pay['date_payment']));
                        $v_amount = str_replace(',', '', $pay['v_amount']);
                        $payment_ref = $pay['payment_ref'] ?? null;
                        
                        $no_giro = null;
                        $bank_giro_id = null;
                        if ($id_payment_method == 2 || $id_payment_method == '2') {
                            $no_giro = $pay['no_giro'] ?? null;
                            $bank_giro_id = $pay['bank_giro_id'] ?? null;
                        }
                        
                        $nkurs = !empty($pay['nkurs']) ? str_replace(',', '', $pay['nkurs']) : null;
                        $code_payment_schdl = $code_invoice . "-" . $i;
                        
                        DB::table('tb_payment_schdl')->insert([
                            'id_invoice' => $id_invoice,
                            'id_customers' => $id_customers,
                            'id_payment_method' => $id_payment_method,
                            'date_payment' => $date_payment,
                            'v_amount' => $v_amount,
                            'payment_ref' => $payment_ref,
                            'status_payment' => 'DRAFT',
                            'date_create' => now(),
                            'date_update' => now(),
                            'no_giro' => $no_giro,
                            'bank_giro_id' => $bank_giro_id,
                            'id_bank' => $id_bank,
                            'code_payment_schdl' => $code_payment_schdl,
                            'nkurs' => $nkurs,
                            'f_dp' => 0
                        ]);
                        $i++;
                    }
                }
            }
            
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Split data sukses']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }
}
