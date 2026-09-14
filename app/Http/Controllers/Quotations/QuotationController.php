<?php

namespace App\Http\Controllers\Quotations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Quotations\Quotation;
use App\Models\Quotations\QuotationDetail;
use App\Models\Quotations\QuotationDetailOption;

class QuotationController extends Controller
{
    /**
     * Get list of quotations
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
                'a.vcurrency',
                'a.ntot_price_netto_amount',
                'a.status_so',
                'a.nkurs'
            )
            ->whereIn('a.status_so', ['DRAFT QUOTATION', 'OUTSTANDING QUOTATION', 'CANCEL QUOTATION', 'QUOTATION']);
            
        // Example filter by id_karyawan if passed
        if ($request->has('id_karyawan')) {
            $query->where('a.id_karyawan', $request->id_karyawan);
        }

        $quotations = $query->orderBy('a.date_so', 'desc')
                            ->orderBy('a.id_so', 'desc')
                            ->get();

        return response()->json([
            'status' => true,
            'data' => $quotations
        ]);
    }

    /**
     * Get detail of a specific quotation
     */
    public function show($id)
    {
        $quotation = DB::table('tb_so_hdr as a')
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
                'd.is_blacklist',
                'b.nm_users',
                'e.nm_type_pembayaran',
                'f.nm_cara_pembayaran',
                'g.nm_waktu_bayar'
            )
            ->where('a.id_so', $id)
            ->first();

        if (!$quotation) {
            return response()->json(['status' => false, 'message' => 'Quotation not found'], 404);
        }

        // Details
        $details = DB::table('tb_so_dtl as a')
            ->leftJoin('m_product as b', 'a.id_product', '=', 'b.id_product')
            ->leftJoin('m_product_satuan as c', 'b.id_product_satuan', '=', 'c.id_product_satuan')
            ->leftJoin('m_product_price as d', 'a.id_product', '=', 'd.id_product')
            ->leftJoin('m_product_brand as e', 'b.id_product_brand', '=', 'e.id_product_brand')
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
                'd.delivery_term',
                'e.nm_product_brand',
                'a.product_price_old',
                'a.ndiskon_persen',
                'a.ntot_product_price',
                'a.ntot_product_price_old',
                'a.ndiskon_amount'
            )
            ->where('a.id_so', $id)
            ->distinct() // Due to possible multiple product_prices
            ->get();

        foreach ($details as $detail) {
            $options = DB::table('tb_so_dtl_options')
                ->where('id_product', $detail->id_product)
                ->where('id_so', $id)
                ->get();
            $detail->options = $options;
        }

        $quotation->details = $details;

        // Approvals
        $approvalsData = DB::table('m_approvals as a')
            ->leftJoin('m_users as b', 'a.approver_id', '=', 'b.id')
            ->select(
                'a.id as id',
                'a.approval_name',
                'a.description',
                'b.nm_users as approver_name',
                'a.status',
                'a.f_request_approval'
            )
            ->where('a.table_name', 'tb_so_hdr')
            ->where('a.record_id', $id)
            ->get();

        $approvals = $approvalsData->map(function ($item) {
            return [
                'id' => $item->id,
                'approval_name' => $item->approval_name,
                'description' => $item->description,
                'approver_name' => $item->approver_name,
                'status' => $item->status,
                'f_request_approval' => $item->f_request_approval,
                'can_approve' => true, // Mocked for now
                'is_super_admin' => true // Mocked for now
            ];
        });

        $quotation->approvals = $approvals;

        return response()->json([
            'status' => true,
            'data' => $quotation
        ]);
    }

    /**
     * Get support data for Quotation Form
     */
    public function supportData(Request $request)
    {
        $last_modified = DB::table('m_settings')->where('setting_key', 'last_modified')->value('setting_value') ?? 30;

        $kurs_usd = DB::table('m_kurs')
            ->where('mata_uang', 'USD')
            ->orderBy('date_create', 'desc')
            ->value('kurs_pembulatan') ?? 16400;

        $query = "select x.* from (
            select
                a.id_product,
                b.code_product,
                b.nm_product,
                b.product_deskripsi,
                a.delivery_term,
                c.nm_product_satuan,
                a.product_price,
                a.product_price_agent,
                case
                    when d.waktu is not null then
                        case
                            when DATEDIFF(now(), d.waktu) > ? then 0
                            else 1
                        end
                    else 0
                end as cek_aktif
            from m_product_price a
            inner join m_product b on (a.id_product = b.id_product)
            inner join m_product_satuan c on (b.id_product_satuan = c.id_product_satuan)
            left join (
                select max(waktu) as waktu, id_product
                from m_product_price_history
                group by id_product
            ) d on d.id_product = a.id_product
            where a.flag_active = 1
        ) as x
        where x.cek_aktif = 1";

        $products = DB::select($query, [$last_modified]);

        // Attach options_product
        $productIds = collect($products)->pluck('id_product')->unique();
        $options = DB::table('m_product_price_opt')
            ->whereIn('id_product', $productIds)
            ->where('f_cancel', '0')
            ->get()
            ->groupBy('id_product');

        foreach ($products as $product) {
            $product->options_product = isset($options[$product->id_product]) ? $options[$product->id_product] : [];
        }

        return response()->json([
            'status' => true,
            'data' => [
                'data_product' => $products,
                'kurs_usd' => $kurs_usd
            ]
        ]);
    }

    /**
     * Store new Quotation
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            // Data assignment 
            $date_estimasi = $request->date_estimasi ? date("Y-m-d", strtotime($request->date_estimasi)) : null;
            $date_so = date("Y-m-d", strtotime($request->date_so ?? date('Y-m-d')));
            
            // Check for revision
            $is_revision = $request->is_revision;
            $id_so_reference = $request->id_so_reference;
            $root_id_so = null;

            if ($is_revision && $id_so_reference) {
                $old_quotation = DB::table('tb_so_hdr')->where('id_so', $id_so_reference)->first();
                if (!$old_quotation) {
                    return response()->json(['status' => false, 'message' => 'Quotation lama tidak ditemukan']);
                }
                
                $root_id_so = $old_quotation->id_so_reference ?: $old_quotation->id_so;
                $jml = DB::table('tb_so_hdr')->where('id_so_reference', $root_id_so)->count();
                $jml++;
                
                $code_so_revisi = "-R" . $jml;
                $root_quotation = DB::table('tb_so_hdr')->where('id_so', $root_id_so)->first();
                $code_so_explode = explode("-R", $root_quotation->code_so);
                $base_code_so = $code_so_explode[0];
                
                $code_so = $base_code_so . $code_so_revisi;
                
                // Set old quotation to CANCEL QUOTATION
                DB::table('tb_so_hdr')->where('id_so', $id_so_reference)->update([
                    'status_so' => 'CANCEL QUOTATION',
                    'flag_cancel' => '1'
                ]);
            } else {
                $year = date("Y", strtotime($date_so));
                $month = date("m", strtotime($date_so));
                $periode = $year . $month;
                $id_counter = 'QO';

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
                    
                    $newNo = str_pad($terakhir, 5, '0', STR_PAD_LEFT);
                } else {
                    $newNo = '00001';
                    DB::table('m_counter')->insert([
                        'id_counter' => $id_counter,
                        'periode' => $periode,
                        'no_counter' => 1
                    ]);
                }

                $prefix = "$id_counter-EMM/$year/$month/";
                $code_so = $prefix . $newNo;
            }

            $ndp_persen = floatval(str_replace(',', '', $request->ndp_persen ?? 0));
            $ndp_amount = floatval(str_replace(',', '', $request->ndp_amount ?? 0));
            $ntenor = intval(str_replace(',', '', $request->ntenor ?? 0));
            $ntenor_amount = floatval(str_replace(',', '', $request->ntenor_amount ?? 0));
            $success_fee = floatval(str_replace(',', '', $request->success_fee ?? 0));

            $freight = $request->freight ?? 1;
            $forklift = $request->forklift ?? 1;
            $teknisi = $request->teknisi ?? 1;
            
            $freight_amount = ($freight == 3) ? floatval(str_replace(',', '', $request->freight_charge)) : 0;
            $forklift_amount = ($forklift == 3) ? floatval(str_replace(',', '', $request->forklift_charge)) : 0;
            
            if ($teknisi == 6) {
                $teknisi_amount = floatval(str_replace(',', '', $request->teknisi_charge));
            } else {
                $teknisi_amount = 0;
            }

            if ($teknisi == 2) {
                $teknisi = $request->teknisi_customer1_select;
            }

            // Get customer address
            $customer = DB::table('m_customers')->where('id_customers', $request->id_customers)->first();
            $customers_address = $customer ? $customer->customers_address : null;

            // Insert Header
            $id_so = DB::table('tb_so_hdr')->insertGetId([
                'id_so_reference' => ($is_revision && $root_id_so) ? $root_id_so : null,
                'code_so' => $code_so,
                'date_so' => $date_so,
                'id_karyawan' => $request->id_karyawan,
                'id_customers' => $request->id_customers,
                'date_estimasi' => $date_estimasi,
                'id_type_pembayaran' => $request->id_type_pembayaran,
                'id_cara_pembayaran' => $request->id_cara_pembayaran,
                'flag_ppn' => $request->flag_ppn,
                'id_waktu_bayar' => $request->id_waktu_bayar,
                'vcurrency' => $request->vcurrency,
                'nkurs' => floatval(str_replace(',', '', $request->nkurs ?? 1)),
                'ndp_amount' => $ndp_amount,
                'ndp_persen' => $ndp_persen,
                'ntenor' => $ntenor,
                'ntenor_amount' => $ntenor_amount,
                'username_create' => $request->username_create,
                'keterangan' => $request->keterangan,
                'delivery_term' => $request->delivery_term_header,
                'code_so_excel' => $request->code_so_excel,
                'no_po_cust' => $request->no_po_cust,
                'internal_notes' => $request->internal_notes,
                'success_fee' => $success_fee,
                'freight' => $freight,
                'forklift' => $forklift,
                'teknisi' => $teknisi,
                'freight_amount' => $freight_amount,
                'forklift_amount' => $forklift_amount,
                'teknisi_amount' => $teknisi_amount,
                'lain_lain' => 0,
                'customers_address' => $customers_address,
                'status_so' => 'DRAFT QUOTATION',
            ]);

            $ntot_price_gross_amount = 0;
            
            // Simpan detail products
            if ($request->has('code_product') && is_array($request->code_product)) {
                foreach ($request->code_product as $key => $code_product) {
                    $nm_product = $request->nm_product[$key] ?? '';
                    $nqty = floatval(str_replace(',', '', $request->qty[$key] ?? 0));
                    $status_barang_array = $request->status_barang[$key] ?? '';
                    
                    $status_barang_explode = explode(" - ", $status_barang_array);
                    $status_barang = $status_barang_explode[0];
                    $indent_amount = null;
                    if ($status_barang == 'INDENT' && isset($status_barang_explode[1])) {
                        $indent_amount = preg_replace('/[^0-9]/', '', $status_barang_explode[1]);
                    }

                    $product_price = floatval(str_replace(',', '', $request->product_price[$key] ?? 0));
                    $nm_satuan = $request->nm_satuan[$key] ?? '';
                    $check_options = (empty($status_barang) && empty($nm_satuan));

                    if (!$check_options) {
                        // Standard Product
                        $product = DB::table('m_product')->where('code_product', $code_product)->first();
                        if ($product) {
                            $product_price_old = 0; // Fetch from m_product_price if needed
                            $ntot_product_price = $product_price * $nqty;
                            $ntot_product_price_old = $product_price_old * $nqty;
                            
                            $ndiskon_amount = $ntot_product_price_old - $ntot_product_price;
                            $ndiskon_persen = ($ntot_product_price_old > 0) ? ($ndiskon_amount / $ntot_product_price_old) * 100 : 0;
                            
                            $ntot_product_price_netto = $ntot_product_price;
                            $ntot_price_gross_amount += $ntot_product_price_netto;

                            DB::table('tb_so_dtl')->insert([
                                'id_so' => $id_so,
                                'id_product' => $product->id_product,
                                'status_barang' => $status_barang,
                                'indent_amount' => $indent_amount,
                                'product_price' => $product_price,
                                'product_price_old' => $product_price_old,
                                'nqty' => $nqty,
                                'ntot_product_price' => $ntot_product_price,
                                'ntot_product_price_old' => $ntot_product_price_old,
                                'ndiskon_persen' => $ndiskon_persen,
                                'ndiskon_amount' => $ndiskon_amount,
                                'ntax' => 0,
                                'ntot_product_price_netto' => $ntot_product_price_netto,
                                'nm_product' => $product->nm_product,
                                'code_product' => $code_product
                            ]);
                        }
                    } else {
                        // Option Product
                        $product_price_old = 0; 
                        $ntot_product_price = $product_price * $nqty;
                        $ntot_product_price_old = $product_price_old * $nqty;
                        $ndiskon_amount = $ntot_product_price_old - $ntot_product_price;
                        $ndiskon_persen = ($ntot_product_price_old > 0) ? ($ndiskon_amount / $ntot_product_price_old) * 100 : 0;
                        
                        $ntot_price_gross_amount += $ntot_product_price;

                        $opt_product = DB::table('m_product_price_opt')->where('id_product_price_opt', $code_product)->first();
                        
                        DB::table('tb_so_dtl_options')->insert([
                            'id_so' => $id_so,
                            'id_product' => $opt_product ? $opt_product->id_product : null,
                            'id_product_price_opt' => $code_product,
                            'nm_product_opt' => $nm_product,
                            'amount' => $product_price,
                            'qty' => $nqty,
                            'amount_old' => $product_price_old,
                            'ntot_opt_price_old' => $ntot_product_price_old,
                            'ntot_opt_price' => $ntot_product_price,
                            'ndiskon_persen' => $ndiskon_persen,
                            'ndiskon_amount' => $ndiskon_amount,
                            'ntot_opt_price_netto' => $ntot_product_price,
                        ]);
                    }
                }
            }

            // Update Header Totals
            DB::table('tb_so_hdr')->where('id_so', $id_so)->update([
                'nppn_amount' => DB::table('m_settings')->where('setting_key', 'ppn')->value('setting_value') ?? 0,
                'ntot_price_gross_amount' => $ntot_price_gross_amount,
                'ntot_price_netto_amount' => $ntot_price_gross_amount,
            ]);

            DB::commit();

            // Insert approvals by scheme 1 (Approval SO)
            $requiresApproval = $this->insertApprovalByScheme(1, [
                'requester_id' => $request->id_user,
                'table_name' => 'tb_so_hdr',
                'record_name' => 'id_so',
                'record_id' => $id_so
            ]);

            return response()->json([
                'status' => true, 
                'message' => 'Quotation successfully created', 
                'kode' => $code_so, 
                'id_so' => $id_so,
                'requires_approval' => $requiresApproval
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Create Quotation Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['status' => false, 'message' => 'Failed to create quotation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update/Revision Quotation
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // Cancel old quotation
            DB::table('tb_so_hdr')->where('id_so', $id)->update(['status_so' => 'CANCEL QUOTATION']);

            // Basically run similar logic as store, but track revision
            // Finding reference ID
            $cek_reference = DB::table('tb_so_hdr')->where('id_so', $id)->first();
            $id_so_reference = $id;
            if ($cek_reference && $cek_reference->id_so_reference != null) {
                $id_so_reference = $cek_reference->id_so_reference;
            }

            // Calculate new revision code
            $jml_so = DB::table('tb_so_hdr')->where('id_so_reference', $id_so_reference)->count();
            // Need to include original in count if no reference was set
            if($id == $id_so_reference) $jml_so++; 

            $code_so_revisi = "-R" . $jml_so;
            $old_code = $cek_reference->code_so;
            $code_so = substr($old_code, 0, 20) . $code_so_revisi;

            $date_estimasi = $request->date_estimasi ? date("Y-m-d", strtotime($request->date_estimasi)) : null;
            $date_so = date("Y-m-d", strtotime($request->date_so ?? date('Y-m-d')));

            $ndp_persen = floatval(str_replace(',', '', $request->ndp_persen ?? 0));
            $ndp_amount = floatval(str_replace(',', '', $request->ndp_amount ?? 0));
            $ntenor = intval(str_replace(',', '', $request->ntenor ?? 0));
            $ntenor_amount = floatval(str_replace(',', '', $request->ntenor_amount ?? 0));
            $success_fee = floatval(str_replace(',', '', $request->success_fee ?? 0));

            $freight = $request->freight ?? 1;
            $forklift = $request->forklift ?? 1;
            $teknisi = $request->teknisi ?? 1;
            
            $freight_amount = ($freight == 3) ? floatval(str_replace(',', '', $request->freight_charge)) : 0;
            $forklift_amount = ($forklift == 3) ? floatval(str_replace(',', '', $request->forklift_charge)) : 0;
            
            if ($teknisi == 6) {
                $teknisi_amount = floatval(str_replace(',', '', $request->teknisi_charge));
            } else {
                $teknisi_amount = 0;
            }

            if ($teknisi == 2) {
                $teknisi = $request->teknisi_customer1_select;
            }

            $customer = DB::table('m_customers')->where('id_customers', $request->id_customers)->first();
            $customers_address = $customer ? $customer->customers_address : null;

            $new_id_so = DB::table('tb_so_hdr')->insertGetId([
                'code_so' => $code_so,
                'date_so' => $date_so,
                'id_karyawan' => $request->id_karyawan,
                'id_customers' => $request->id_customers,
                'date_estimasi' => $date_estimasi,
                'id_type_pembayaran' => $request->id_type_pembayaran,
                'id_cara_pembayaran' => $request->id_cara_pembayaran,
                'flag_ppn' => $request->flag_ppn,
                'id_waktu_bayar' => $request->id_waktu_bayar,
                'vcurrency' => $request->vcurrency,
                'nkurs' => floatval(str_replace(',', '', $request->nkurs ?? 1)),
                'ndp_amount' => $ndp_amount,
                'ndp_persen' => $ndp_persen,
                'ntenor' => $ntenor,
                'ntenor_amount' => $ntenor_amount,
                'username_create' => $request->username_create,
                'keterangan' => $request->keterangan,
                'delivery_term' => $request->delivery_term_header,
                'id_so_reference' => $id_so_reference,
                'code_so_excel' => $request->code_so_excel,
                'no_po_cust' => $request->no_po_cust,
                'internal_notes' => $request->internal_notes,
                'success_fee' => $success_fee,
                'freight' => $freight,
                'forklift' => $forklift,
                'teknisi' => $teknisi,
                'freight_amount' => $freight_amount,
                'forklift_amount' => $forklift_amount,
                'teknisi_amount' => $teknisi_amount,
                'lain_lain' => 0,
                'customers_address' => $customers_address,
                'status_so' => 'DRAFT QUOTATION',
            ]);

            $ntot_price_gross_amount = 0;
            
            // Detail products insertion (Similar to store method)
            if ($request->has('code_product') && is_array($request->code_product)) {
                foreach ($request->code_product as $key => $code_product) {
                    $nm_product = $request->nm_product[$key] ?? '';
                    $nqty = floatval(str_replace(',', '', $request->qty[$key] ?? 0));
                    $status_barang_array = $request->status_barang[$key] ?? '';
                    
                    $status_barang_explode = explode(" - ", $status_barang_array);
                    $status_barang = $status_barang_explode[0];
                    $indent_amount = null;
                    if ($status_barang == 'INDENT' && isset($status_barang_explode[1])) {
                        $indent_amount = preg_replace('/[^0-9]/', '', $status_barang_explode[1]);
                    }

                    $product_price = floatval(str_replace(',', '', $request->product_price[$key] ?? 0));
                    $nm_satuan = $request->nm_satuan[$key] ?? '';
                    $check_options = (empty($status_barang) && empty($nm_satuan));

                    if (!$check_options) {
                        $product = DB::table('m_product')->where('code_product', $code_product)->first();
                        if ($product) {
                            $product_price_old = 0; 
                            $ntot_product_price = $product_price * $nqty;
                            $ntot_product_price_old = $product_price_old * $nqty;
                            
                            $ndiskon_amount = $ntot_product_price_old - $ntot_product_price;
                            $ndiskon_persen = ($ntot_product_price_old > 0) ? ($ndiskon_amount / $ntot_product_price_old) * 100 : 0;
                            
                            $ntot_product_price_netto = $ntot_product_price;
                            $ntot_price_gross_amount += $ntot_product_price_netto;

                            DB::table('tb_so_dtl')->insert([
                                'id_so' => $new_id_so,
                                'id_product' => $product->id_product,
                                'status_barang' => $status_barang,
                                'indent_amount' => $indent_amount,
                                'product_price' => $product_price,
                                'product_price_old' => $product_price_old,
                                'nqty' => $nqty,
                                'ntot_product_price' => $ntot_product_price,
                                'ntot_product_price_old' => $ntot_product_price_old,
                                'ndiskon_persen' => $ndiskon_persen,
                                'ndiskon_amount' => $ndiskon_amount,
                                'ntax' => 0,
                                'ntot_product_price_netto' => $ntot_product_price_netto,
                                'nm_product' => $product->nm_product,
                                'code_product' => $code_product
                            ]);
                        }
                    } else {
                        $product_price_old = 0; 
                        $ntot_product_price = $product_price * $nqty;
                        $ntot_product_price_old = $product_price_old * $nqty;
                        $ndiskon_amount = $ntot_product_price_old - $ntot_product_price;
                        $ndiskon_persen = ($ntot_product_price_old > 0) ? ($ndiskon_amount / $ntot_product_price_old) * 100 : 0;
                        
                        $ntot_price_gross_amount += $ntot_product_price;

                        $opt_product = DB::table('m_product_price_opt')->where('id_product_price_opt', $code_product)->first();
                        
                        DB::table('tb_so_dtl_options')->insert([
                            'id_so' => $new_id_so,
                            'id_product' => $opt_product ? $opt_product->id_product : null,
                            'id_product_price_opt' => $code_product,
                            'nm_product_opt' => $nm_product,
                            'amount' => $product_price,
                            'qty' => $nqty,
                            'amount_old' => $product_price_old,
                            'ntot_opt_price_old' => $ntot_product_price_old,
                            'ntot_opt_price' => $ntot_product_price,
                            'ndiskon_persen' => $ndiskon_persen,
                            'ndiskon_amount' => $ndiskon_amount,
                            'ntot_opt_price_netto' => $ntot_product_price,
                        ]);
                    }
                }
            }

            DB::table('tb_so_hdr')->where('id_so', $new_id_so)->update([
                'nppn_amount' => DB::table('m_settings')->where('setting_key', 'ppn')->value('setting_value') ?? 0,
                'ntot_price_gross_amount' => $ntot_price_gross_amount,
                'ntot_price_netto_amount' => $ntot_price_gross_amount,
            ]);

            DB::commit();

            // Insert approvals by scheme 1 (Approval SO) for the new revision
            $requiresApproval = $this->insertApprovalByScheme(1, [
                'requester_id' => $request->id_user ?? 4,
                'table_name' => 'tb_so_hdr',
                'record_name' => 'id_so',
                'record_id' => $new_id_so
            ]);

            return response()->json([
                'status' => true, 
                'message' => 'Quotation successfully revised', 
                'kode' => $code_so,
                'requires_approval' => $requiresApproval
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Update Quotation Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json(['status' => false, 'message' => 'Failed to update quotation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Insert approval records for a scheme 
     * 
     * @param int $scheme_id The scheme ID (1 = Approval SO)
     * @param array $data ['requester_id', 'table_name', 'record_name', 'record_id']
     */
    private function insertApprovalByScheme(int $scheme_id, array $data): bool
    {
        // Get all rule IDs linked to this scheme
        $ruleIds = DB::table('m_scheme_rules')
            ->where('scheme_id', $scheme_id)
            ->pluck('rule_id');

        if ($ruleIds->isEmpty()) {
            return false;
        }

        // Get all active rules
        $rules = DB::table('m_approval_rules')
            ->whereIn('id', $ruleIds)
            ->where('is_active', 1)
            ->get();

        $now = now();

        $hasPending = false;
        foreach ($rules as $rule) {
            $isPassed = $this->evaluateRule($rule->module_name, $data['record_id']);
            $status = $isPassed ? 'passed' : 'Pending';
            
            if ($status === 'Pending' && $rule->module_name !== 'SO_004') {
                $hasPending = true;
            }

            DB::table('m_approvals')->insert([
                'requester_id'       => $data['requester_id'],
                'approver_id'        => null,
                'module_name'        => $rule->module_name,
                'table_name'         => $data['table_name'],
                'record_name'        => $data['record_name'],
                'record_id'          => $data['record_id'],
                'status_column_name' => $rule->status_column_name ?? null,
                'new_status_approve' => $rule->new_status_approve ?? null,
                'new_status_reject'  => $rule->new_status_reject ?? null,
                'status'             => $status,
                'approval_name'      => $rule->approval_name,
                'description'        => $rule->description,
                'rule'               => $rule->rule,
                'approval_type'      => $rule->approval_type ?? 'auto',
                'f_request_approval' => 0,
                'created_at'         => $now,
                'updated_at'         => $now,
                'request_date'       => $now,
            ]);
        }
        
        return $hasPending;
    }

    /**
     * Evaluate approval rule logic
     */
    private function evaluateRule(string $moduleName, int $idSo): bool
    {
        $soHdr = DB::table('tb_so_hdr')->where('id_so', $idSo)->first();
        if (!$soHdr) return false;

        switch ($moduleName) {
            case 'SO_001':
                // Konfirmasi Success Fee
                return !($soHdr->success_fee > 0);

            case 'SO_002':
                // Diskon barang SO GM
                $karyawan = DB::table('m_karyawan')->where('id_karyawan', $soHdr->id_karyawan)->first();
                $isAgent = $karyawan && $karyawan->flag_agent == 1;
                $maxDiskon = DB::table('tb_so_dtl')->where('id_so', $idSo)->max('ndiskon_persen') ?? 0;

                if (!$isAgent) {
                    if ($maxDiskon > 5) return true;
                    if ($maxDiskon > 3) return false;
                } else {
                    if ($maxDiskon > 6) return true;
                }
                return true;

            case 'SO_003':
                // Diskon barang SO Direktur
                $karyawan = DB::table('m_karyawan')->where('id_karyawan', $soHdr->id_karyawan)->first();
                $isAgent = $karyawan && $karyawan->flag_agent == 1;
                $maxDiskon = DB::table('tb_so_dtl')->where('id_so', $idSo)->max('ndiskon_persen') ?? 0;

                if (!$isAgent) {
                    if ($maxDiskon > 5) return false;
                    if ($maxDiskon > 3) return true;
                } else {
                    if ($maxDiskon > 6) return false;
                }
                return true;

            case 'SO_004':
                // Batal Quotation
                return false;

            case 'SO_005':
                // Diskon Barang Option
                $hasDiscount = DB::table('tb_so_dtl_options')
                    ->where('id_so', $idSo)
                    ->where('ndiskon_persen', '>', 0)
                    ->exists();
                
                return !$hasDiscount;

            default:
                return false;
        }
    }

    public function confirmToSO(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $quotation = DB::table('tb_so_hdr')->where('id_so', $id)->first();
            if (!$quotation) {
                return response()->json(['status' => false, 'message' => 'Data tidak ditemukan']);
            }

            // Generate SO code EMM/MMM/YY-NNN
            $current_date = date('Y-m-d');
            $year = date("y", strtotime($current_date)); // 2 digit year
            $month = date("m", strtotime($current_date));
            $monthStr = strtoupper(date("M", strtotime($current_date))); // JUL, SEP
            $periode = date("Y", strtotime($current_date)) . $month;
            
            // Check counter for 'SO BARU'
            $id_counter = 'SO BARU';
            $cek = DB::table('m_counter')
                ->where('id_counter', $id_counter)
                ->whereRaw('SUBSTRING(periode, 1, 4) = ?', [date("Y", strtotime($current_date))])
                ->first();

            if ($cek) {
                $terakhir = $cek->no_counter + 1;
                DB::table('m_counter')
                    ->where('id_counter', $id_counter)
                    ->whereRaw('SUBSTRING(periode, 1, 4) = ?', [date("Y", strtotime($current_date))])
                    ->update(['no_counter' => $terakhir]);
                
                // Usually 3 digits as requested (e.g. 191)
                $newNo = str_pad($terakhir, 3, '0', STR_PAD_LEFT);
            } else {
                $newNo = '001';
                DB::table('m_counter')->insert([
                    'id_counter' => $id_counter,
                    'periode' => $periode,
                    'no_counter' => 1
                ]);
            }

            $prefix = "EMM/$monthStr/$year-";
            $new_code_so = $prefix . $newNo;

            // Move old code to code_quotation, old date to date_quotation, and update status
            DB::table('tb_so_hdr')
                ->where('id_so', $id)
                ->update([
                    'code_quotation' => $quotation->code_so,
                    'date_quotation' => $quotation->date_so,
                    'code_so' => $new_code_so,
                    'date_so' => $current_date,
                    'status_so' => 'SALE TO INVOICE'
                ]);

            DB::commit();

            return response()->json([
                'status' => true, 
                'message' => 'Berhasil Confirm to SO', 
                'new_code_so' => $new_code_so
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()]);
        }
    }
}
