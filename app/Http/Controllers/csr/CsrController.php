<?php

namespace App\Http\Controllers\csr;

use App\Http\Controllers\Controller;
use App\Models\csr\AfsCsr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CsrController extends Controller
{
    public function index()
    {
        $csrs = DB::table('tb_afs_csr')
            ->select(
                'tb_afs_csr.*', 
                'm_karyawan.nm_karyawan', 
                'm_customers.nm_customers', 
                'm_product.nm_product', 
                'm_product.code_product'
            )
            ->leftJoin('m_karyawan', 'tb_afs_csr.id_karyawan', '=', 'm_karyawan.id_karyawan')
            ->leftJoin('m_customers', 'tb_afs_csr.id_customers', '=', 'm_customers.id_customers')
            ->leftJoin('m_product', 'tb_afs_csr.id_product', '=', 'm_product.id_product')
            ->orderBy('tb_afs_csr.id_afs_csr', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $csrs
        ]);
    }

    public function show($id)
    {
        $query = DB::table('tb_afs_csr')
            ->select(
                'tb_afs_csr.*', 
                'm_karyawan.nm_karyawan', 
                'm_customers.nm_customers', 
                'm_customers.customers_mobile', 
                'tb_so_hdr.customers_address', 
                'm_product.nm_product', 
                'm_product.code_product', 
                'm_product.id_product_kategori', 
                'tb_so_hdr.keterangan', 
                'tb_so_hdr.internal_notes', 
                'm_product_kategori.nm_product_kategori'
            )
            ->leftJoin('m_karyawan', 'tb_afs_csr.id_karyawan', '=', 'm_karyawan.id_karyawan')
            ->leftJoin('m_customers', 'tb_afs_csr.id_customers', '=', 'm_customers.id_customers')
            ->leftJoin('m_product', 'tb_afs_csr.id_product', '=', 'm_product.id_product')
            ->leftJoin('tb_do_hdr', 'tb_afs_csr.do_code', '=', 'tb_do_hdr.code_do')
            ->leftJoin('m_product_kategori', 'm_product.id_product_kategori', '=', 'm_product_kategori.id_product_kategori')
            ->leftJoin('tb_so_hdr', 'tb_do_hdr.id_so', '=', 'tb_so_hdr.id_so');

        if (is_numeric($id)) {
            $csr = $query->where('tb_afs_csr.id_afs_csr', $id)->first();
        } else {
            $csr_code = str_replace('.', '/', $id);
            $csr = $query->where('tb_afs_csr.csr_code', $csr_code)->first();
        }

        if (!$csr) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        // Fetch CST List related to this CSR
        $cst_list = DB::table('tb_afs_cst')
            ->where('id_afs_csr', $csr->id_afs_csr)
            ->where('cst_code', '!=', 'kosong')
            ->get();
            
        $csr->cst_list = $cst_list;

        return response()->json([
            'status' => true,
            'data' => $csr
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'sn_number' => 'required',
            'id_karyawan' => 'required',
            'lokasi' => 'required',
            'lap_kerusakan' => 'required',
            'sts_pasang' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $tgl_delivered = $request->tgl_delivered ?? date('Y-m-d');
            $date_request = $request->date_request ?? date('Y-m-d');

            $t_d = date('Y-m-d', strtotime($tgl_delivered));
            $d_r = date('Y-m-d', strtotime($date_request));

            $tambahthn = date('Y-m-d', strtotime('+1 year', strtotime($t_d)));
            $do_code = $request->do_code;

            // Check TB SO DTL EXTEND WARRANTY
            if ($do_code) {
                $totalConfirm = DB::table('tb_so_dtl_extend_warranty as a')
                    ->join('tb_so_hdr as b', 'a.so_id', '=', 'b.id_so')
                    ->join('tb_do_hdr as c', 'b.id_so', '=', 'c.id_so')
                    ->where('c.code_do', $do_code)
                    ->where('a.status', 'CONFIRM')
                    ->sum('a.name');

                if ($totalConfirm > 0) {
                    $tambahthn = date('Y-m-d', strtotime('+' . $totalConfirm . ' days', strtotime($tambahthn)));
                }
            }

            // Generate Kode CSR
            $today = date('Y');
            $todayM = date('m');
            $prefix = 'CSR-EMM/' . $today . '/' . $todayM . '/';
            
            // Dapatkan nomor urut terakhir secara keseluruhan berdasarkan angka diujungnya
            $lastCsr = DB::table('tb_afs_csr')
                ->where('csr_code', 'LIKE', 'CSR-EMM/%')
                ->orderByRaw('CAST(SUBSTRING(csr_code, 17) AS UNSIGNED) DESC')
                ->first();
                
            $noUrut = 0;
            if ($lastCsr && strlen($lastCsr->csr_code) >= 21) {
                // CSR-EMM/YYYY/MM/XXXXX (prefix 16 char, number 5 char)
                $noUrut = (int)substr($lastCsr->csr_code, 16);
            }
            $noUrut++;
            $NewID = $prefix . sprintf('%05s', $noUrut);

            // Upload Photo
            $link_foto = null;
            if ($request->hasFile('link_foto')) {
                $file = $request->file('link_foto');
                $filename = time() . '_' . $file->hashName();
                $file->move(public_path('assets/upload/afs/'), $filename);
                $link_foto = $filename;
            }

            $csr_by = $request->csr_by ?? 'Admin'; 
            $id_customers = $request->id_customers ?? $request->customers;
            
            $sts_pasang = $request->sts_pasang;
            if ($sts_pasang === 'Pasang Baru') {
                $sts_pasang = 1;
            } elseif ($sts_pasang === 'Service') {
                $sts_pasang = 0;
            }

            $w_t = 12; // Assuming default warranty time is 12 months
            $w_e = $tambahthn; // Usually calculate based on warranty time

            // Insert CSR
            $csr = AfsCsr::create([
                'csr_code' => $NewID,
                'csr_date' => $d_r,
                'id_customers' => $id_customers,
                'id_karyawan' => $request->id_karyawan,
                'barcode' => $request->sn_number,
                'do_code' => $do_code,
                'waranty_start' => $t_d,
                'waranty_time' => $w_t,
                'waranty_end' => $w_e,
                'lap_kerusakan' => $request->lap_kerusakan,
                'id_product' => $request->id_product,
                'lokasi' => $request->lokasi,
                'csr_input_date' => date('Y-m-d H:i:s'),
                'csr_by' => $csr_by,
                'csr_status' => 'DRAFT',
                'sts_pasang' => $sts_pasang,
                'mesin_lama' => $request->mesin_lama,
                'image' => $link_foto
            ]);

            // Insert placeholder tb_afs_cst
            DB::table('tb_afs_cst')->insert([
                'id_afs_csr' => $csr->id_afs_csr,
                'cst_code' => 'kosong',
                'cst_date' => null,
                'status' => null
            ]);

            // Trans log
            $maxLog = DB::table('tb_trans_swo_log')->max('id_trans_swo_log');
            DB::table('tb_trans_swo_log')->insert([
                'id_trans_swo_log' => $maxLog + 1,
                'translog_date' => date('Y-m-d H:i:s'),
                'kode_trans' => $NewID,
                'user_id' => $csr_by,
                'action' => 'insert CSR',
                'table_name' => 'tb_afs_csr',
                'form' => 'CSR',
            ]);

            $nm_cs_wa = DB::table('m_customers')->where('id_customers', $id_customers)->value('nm_customers') ?? '';
            $nm_cs_pr = DB::table('m_product')->where('id_product', $request->id_product)->value('code_product') ?? '';

            $message = "Telah diinput oleh " . $csr_by . "\n";
            $message .= "tanggal : " . date('d-m-Y H:i:s') . "\n";
            $message .= "\nNo CSR : " . $NewID . "\n";
            $message .= "\nCustomer : " . $nm_cs_wa . "\n";
            $message .= "\nModel mesin : " . $nm_cs_pr . "\n";
            $message .= "\nTgl Kirim Mesin : " . $tgl_delivered . "\n";
            $message .= "\nTanggal Request : " . $d_r . "\n";
            $message .= "\nKeterangan : " . $request->lap_kerusakan;

            $maxWa = DB::table('tb_message_wa')->max('id_message_wa');
            DB::table('tb_message_wa')->insert([
                'id_message_wa' => $maxWa + 1,
                'mobile_number' => '62818777535-1541378496',
                'message' => $message,
                'username_create' => $csr_by,
                'flag_status' => '0',
                'date_create' => date('Y-m-d H:i:s'),
                'date_update' => date('Y-m-d H:i:s'),
                'flag_group' => '1',
            ]);



            DB::commit();

            return response()->json([
                'status' => true,
                'kode' => $NewID,
                'message' => 'Data CSR berhasil ditambahkan'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CSR Store Error: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        if (is_numeric($id)) {
            $csr = AfsCsr::where('id_afs_csr', $id)->first();
            $csr_code = $csr ? $csr->csr_code : null;
        } else {
            $csr_code = str_replace('.', '/', $id);
            $csr = AfsCsr::where('csr_code', $csr_code)->first();
        }

        if (!$csr) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        if ($csr->approved_csr_by != null) {
            return response()->json(['status' => false, 'message' => 'CSR sudah dikonfirmasi, tidak bisa diedit'], 403);
        }

        DB::beginTransaction();
        try {
            $link_foto = $csr->image;
            if ($request->hasFile('link_foto')) {
                $file = $request->file('link_foto');
                $filename = time() . '_' . $file->hashName();
                $file->move(public_path('assets/upload/afs/'), $filename);
                $link_foto = $filename;
            }

            $sts_pasang = $request->sts_pasang ?? $csr->sts_pasang;
            if ($sts_pasang === 'Pasang Baru') {
                $sts_pasang = 1;
            } elseif ($sts_pasang === 'Service') {
                $sts_pasang = 0;
            }

            $id_customers = $request->id_customers ?? $request->customers ?? $csr->id_customers;
            $csr_by = $request->csr_by;
            
            $csr->update([
                'id_customers' => $id_customers,
                'csr_date' => $request->csr_date ?? $csr->csr_date,
                'id_karyawan' => $request->id_karyawan ?? $csr->id_karyawan,
                'lap_kerusakan' => $request->lap_kerusakan ?? $csr->lap_kerusakan,
                'lokasi' => $request->lokasi ?? $csr->lokasi,
                'sts_pasang' => $sts_pasang,
                'mesin_lama' => $request->mesin_lama ?? $csr->mesin_lama,
                'image' => $link_foto
            ]);

            $maxLog = DB::table('tb_trans_swo_log')->max('id_trans_swo_log');
            DB::table('tb_trans_swo_log')->insert([
                'id_trans_swo_log' => $maxLog + 1,
                'translog_date' => date('Y-m-d H:i:s'),
                'kode_trans' => $csr_code,
                'user_id' => $csr_by,
                'action' => 'Ubah CSR',
                'table_name' => 'tb_afs_csr',
                'form' => 'CSR',
            ]);



            DB::commit();
            return response()->json(['status' => true, 'message' => 'Data berhasil diupdate']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CSR Update Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal mengupdate data: ' . $e->getMessage()], 500);
        }
    }

    public function confirm(Request $request, $id)
    {
        if (is_numeric($id)) {
            $csr = AfsCsr::where('id_afs_csr', $id)->first();
            $csr_code = $csr ? $csr->csr_code : null;
        } else {
            $csr_code = str_replace('.', '/', $id);
            $csr = AfsCsr::where('csr_code', $csr_code)->first();
        }

        if (!$csr) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        DB::beginTransaction();
        try {
            $csr_by = $request->csr_by;
            $cst_date = $request->cst_date ?? date('Y-m-d');

            if (strtotime($cst_date) < strtotime($csr->csr_date)) {
                return response()->json(['status' => false, 'message' => 'Tanggal CST tidak boleh kurang dari tanggal CSR'], 400);
            }

            $csr->update([
                'csr_status' => 'OUTSTANDING',
                'approved_csr_by' => $csr_by,
                'csr_approve_date' => date('Y-m-d H:i:s')
            ]);

            // Handling CST insertion/update
            $cek_cst = DB::table('tb_afs_cst')
                ->where('id_afs_csr', $csr->id_afs_csr)
                ->where('cst_code', 'kosong')
                ->whereNull('status')
                ->first();
                
            $today = date('Y');
            $todayM = date('m');
            // Assuming generating CST Code if needed
            $cst_code = $request->cst_code;
            if (!$cst_code) {
                $maxCst = DB::table('tb_afs_cst')
                    ->whereRaw("SUBSTRING(cst_code, 9, 4) = ?", [$today])
                    ->whereRaw("SUBSTRING(cst_code, 14, 2) = ?", [$todayM])
                    ->max(DB::raw("SUBSTRING(cst_code, 17, 5)"));
                $noUrutCst = (int) $maxCst + 1;
                $cst_code = 'CST-EMM/' . $today . '/' . $todayM . '/' . sprintf('%05s', $noUrutCst);
            }

            if ($cek_cst) {
                DB::table('tb_afs_cst')->where('id_afs_cst', $cek_cst->id_afs_cst)->update([
                    'cst_code' => $cst_code,
                    'cst_date' => date('Y-m-d', strtotime($cst_date)),
                    'status' => 'OUTSTANDING'
                ]);
            } else {
                DB::table('tb_afs_cst')->insert([
                    'id_afs_csr' => $csr->id_afs_csr,
                    'cst_code' => $cst_code,
                    'cst_date' => date('Y-m-d', strtotime($cst_date)),
                    'status' => 'OUTSTANDING'
                ]);
            }



            DB::commit();
            return response()->json(['status' => true, 'message' => 'CSR Berhasil Dikonfirmasi', 'cst_code' => $cst_code]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CSR Confirm Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Gagal konfirmasi data: ' . $e->getMessage()], 500);
        }
    }

    public function cancel(Request $request, $id)
    {
        if (is_numeric($id)) {
            $csr = AfsCsr::where('id_afs_csr', $id)->first();
            $csr_code = $csr ? $csr->csr_code : null;
        } else {
            $csr_code = str_replace('.', '/', $id);
            $csr = AfsCsr::where('csr_code', $csr_code)->first();
        }

        if (!$csr) {
            return response()->json(['status' => false, 'message' => 'Data tidak ditemukan'], 404);
        }

        $csr->update([
            'csr_status' => 'CANCEL',
            'f_cancel' => 1,
            'alasan_cancel' => $request->memo,
            'csr_cancel_date' => date('Y-m-d H:i:s')
        ]);



        return response()->json(['status' => true, 'message' => 'CSR Berhasil Dibatalkan']);
    
    }

    public function checkSerialNumber(Request $request)
    {
        $sn = $request->query('sn');

        if (!$sn) {
            return response()->json(['status' => false, 'message' => 'Serial Number is required'], 400);
        }

        $result = DB::table('tb_do_dtl')
            ->join('tb_do_hdr', 'tb_do_dtl.id_do', '=', 'tb_do_hdr.id_do')
            ->join('tb_so_hdr', 'tb_do_hdr.id_so', '=', 'tb_so_hdr.id_so')
            ->where('tb_do_dtl.nbarcode', $sn)
            ->where('tb_so_hdr.flag_cancel', 0)
            ->select(
                'tb_do_hdr.code_do',
                'tb_so_hdr.status_so'
            )
            ->first();

        if ($result) {
            return response()->json(['status' => true, 'data' => $result]);
        } else {
            return response()->json(['status' => false, 'message' => 'Serial Number tidak ditemukan atau SO dibatalkan']);
        }
    }

    public function getFormOptions(Request $request)
    {
        $productsQuery = \App\Models\Product\Product::where('flag_active', 1);

        $products = $productsQuery->select('m_product.id_product as value', 'm_product.nm_product as label')
            ->distinct()
            ->get();

        $customersQuery = \App\Models\Customers\Customer::select('m_customers.id_customers as value', 'm_customers.nm_customers as label', 'm_customers.provinsi');
        
        $customers = $customersQuery->get();

        $karyawan = \App\Models\Employee\Employee::where('flag_status', 1)
            ->select('id_karyawan as value', 'nm_karyawan as label')
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'products' => $products,
                'customers' => $customers,
                'karyawan' => $karyawan
            ]
        ]);
    }

    public function getBarcodeData(Request $request)
    {
        $barcode = trim($request->query('barcode', ''));
        if (!$barcode) {
            return response()->json(['status' => false, 'message' => 'Barcode is required'], 400);
        }
        
        Log::info("Barcode requested: " . $barcode);
        $data = DB::table('tb_do_dtl as a')
            ->join('tb_do_hdr as b', 'a.id_do', '=', 'b.id_do')
            ->join('tb_so_hdr as c', 'b.id_so', '=', 'c.id_so')
            ->leftJoin('m_customers as d', 'b.id_customers', '=', 'd.id_customers')
            ->select(
                'c.code_so',
                'c.status_so',
                'a.nbarcode',
                'b.date_delivery',
                'b.code_do',
                'a.id_product',
                'b.status_do',
                'b.id_customers',
                'd.provinsi'
            )
            ->where('a.nbarcode', 'LIKE', '%' . $barcode . '%')
            ->where('c.flag_cancel', '0')
            ->orderBy('a.id_do_dtl', 'desc')
            ->first();

        if ($data) {
            return response()->json(['status' => true, 'data' => $data]);
        }

        return response()->json(['status' => false, 'message' => 'Barcode tidak ditemukan'], 404);
    }
}
