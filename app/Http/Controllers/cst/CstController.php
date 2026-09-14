<?php

namespace App\Http\Controllers\cst;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\cst\AfsCst;

class CstController extends Controller
{
    public function index()
    {
        $csts = DB::table('tb_afs_cst')
            ->select(
                'tb_afs_cst.*',
                'tb_afs_csr.csr_code',
                'tb_afs_csr.approved_csr_by',
                'm_customers.nm_customers',
                'm_product.nm_product',
                'm_karyawan.nm_karyawan'
            )
            ->join('tb_afs_csr', 'tb_afs_cst.id_afs_csr', '=', 'tb_afs_csr.id_afs_csr')
            ->leftJoin('m_customers', 'tb_afs_csr.id_customers', '=', 'm_customers.id_customers')
            ->leftJoin('m_product', 'tb_afs_csr.id_product', '=', 'm_product.id_product')
            ->leftJoin('m_karyawan', 'tb_afs_csr.id_karyawan', '=', 'm_karyawan.id_karyawan')
            ->where('tb_afs_cst.cst_code', '!=', 'kosong')
            ->orderBy('tb_afs_cst.id_afs_cst', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $csts
        ]);
    }

    public function show($id)
    {
        $query = DB::table('tb_afs_cst')
            ->select(
                'tb_afs_cst.*',
                'tb_afs_csr.csr_code',
                'tb_afs_csr.csr_date',
                'tb_afs_csr.so_date',
                'tb_afs_csr.id_customers',
                'tb_afs_csr.id_product',
                'tb_afs_csr.id_karyawan',
                'tb_afs_csr.do_code',
                'tb_afs_csr.lap_kerusakan',
                'tb_afs_csr.barcode',
                'tb_afs_csr.lokasi',
                'tb_afs_csr.sts_pasang',
                'tb_afs_csr.image',
                'tb_afs_csr.waranty_start',
                'tb_afs_csr.waranty_time',
                'tb_afs_csr.waranty_end',
                'm_customers.nm_customers',
                'm_customers.customers_address',
                'm_customers.customers_mobile',
                'm_product.nm_product',
                'm_product.code_product',
                'm_product_kategori.nm_product_kategori',
                'm_karyawan.nm_karyawan',
                'tb_so_hdr.keterangan'
            )
            ->join('tb_afs_csr', 'tb_afs_cst.id_afs_csr', '=', 'tb_afs_csr.id_afs_csr')
            ->leftJoin('m_customers', 'tb_afs_csr.id_customers', '=', 'm_customers.id_customers')
            ->leftJoin('m_product', 'tb_afs_csr.id_product', '=', 'm_product.id_product')
            ->leftJoin('m_product_kategori', 'm_product.id_product_kategori', '=', 'm_product_kategori.id_product_kategori')
            ->leftJoin('m_karyawan', 'tb_afs_csr.id_karyawan', '=', 'm_karyawan.id_karyawan')
            ->leftJoin('tb_do_hdr', 'tb_afs_csr.do_code', '=', 'tb_do_hdr.code_do')
            ->leftJoin('tb_so_hdr', 'tb_do_hdr.id_so', '=', 'tb_so_hdr.id_so');

        if (is_numeric($id)) {
            $cst = $query->where('tb_afs_cst.id_afs_cst', $id)->first();
        } else {
            $cst_code = str_replace('.', '/', $id);
            $cst = $query->where('tb_afs_cst.cst_code', $cst_code)->first();
        }

        if (!$cst) {
            return response()->json([
                'success' => false,
                'message' => 'CST tidak ditemukan'
            ], 404);
        }

        // Get LKT List (like bacadetail2)
        $lkt_list = DB::table('tb_afs_lkt')
            ->select('tb_afs_lkt.*')
            ->where('id_afs_cst', $cst->id_afs_cst)
            ->orderBy('lkt_code', 'DESC')
            ->get();

        $cst->lkt_list = $lkt_list;
        
        // Map fields to match what frontend expects (pic_name, phone, nm_branch) if they are named differently
        $cst->phone = $cst->customers_mobile;
        $cst->nm_branch = 'Cabang Utama'; // If branch is not in the tables provided

        return response()->json([
            'success' => true,
            'data' => $cst
        ]);
    }

    public function update(Request $request, $id)
    {
        $action = $request->input('action'); // 'DONE' or 'CANCEL'
        $username = $request->input('cst_by', 'Admin'); // Get from request or auth

        if (is_numeric($id)) {
            $cst = DB::table('tb_afs_cst')->where('id_afs_cst', $id)->first();
        } else {
            $cst_code = str_replace('.', '/', $id);
            $cst = DB::table('tb_afs_cst')->where('cst_code', $cst_code)->first();
        }

        if (!$cst) {
            return response()->json(['success' => false, 'message' => 'CST tidak ditemukan.'], 404);
        }

        if ($action === 'DONE') {
            // Check LKT Done status
            $lktCount = DB::table('tb_afs_lkt')
                ->where('id_afs_cst', $cst->id_afs_cst)
                ->where('flag_done', 'DONE')
                ->where('f_cancel', 0)
                ->count();
                
            $totalLkt = DB::table('tb_afs_lkt')
                ->where('id_afs_cst', $cst->id_afs_cst)
                ->where('f_cancel', 0)
                ->count();

            if ($totalLkt > 0 && $lktCount < $totalLkt) {
                // Return error if there are unfinished LKTs
                 return response()->json([
                    'success' => false, 
                    'message' => 'LKT Belum DONE oleh Teknisi !!'
                ], 400);
            }

            // Update tb_afs_cst
            DB::table('tb_afs_cst')->where('id_afs_cst', $cst->id_afs_cst)->update([
                'status' => 'DONE',
                'cst_approve_date' => date('Y-m-d H:i:s'),
                'approved_cst_by' => $username,
                'cst_done_date' => date('Y-m-d H:i:s'),
                'done_cst_by' => $username
            ]);

            // Update tb_afs_csr
            DB::table('tb_afs_csr')->where('id_afs_csr', $cst->id_afs_csr)->update([
                'csr_status' => 'DONE',
                'f_cancel' => 0
            ]);

            $csrData = DB::table('tb_afs_csr')->where('id_afs_csr', $cst->id_afs_csr)->first();
            if ($csrData) {
                DB::table('m_notifikasi')->insert([
                    'user_id' => $csrData->id_customers,
                    'id_users_level' => 18,
                    'kode_trans' => $cst->cst_code,
                    'judul' => 'CST Selesai',
                    'pesan' => "CST {$cst->cst_code} telah selesai (DONE).",
                    'action' => 'Update',
                    'is_read' => false,
                    'created_at' => now(),
                ]);
            }

            // Translog (mocking Mmaster.php translog logic)
            $this->insertLog('Done CST', $cst->cst_code, $username);

            return response()->json([
                'success' => true,
                'message' => 'CST berhasil ditandai sebagai DONE.'
            ]);
        } 
        else if ($action === 'CANCEL') {
            
            DB::table('tb_afs_csr')->where('id_afs_csr', $cst->id_afs_csr)->update([
                'csr_status' => 'OUTSTANDING',
                'f_cancel' => 0
            ]);

            DB::table('tb_afs_cst')->where('id_afs_cst', $cst->id_afs_cst)->update([
                'status' => 'CANCEL',
                'cst_ignore_date' => date('Y-m-d H:i:s'),
                'ignore_cst_by' => $username
            ]);

            $csrData = DB::table('tb_afs_csr')->where('id_afs_csr', $cst->id_afs_csr)->first();
            if ($csrData) {
                DB::table('m_notifikasi')->insert([
                    'user_id' => $csrData->id_customers,
                    'id_users_level' => 18,
                    'kode_trans' => $cst->cst_code,
                    'judul' => 'CST Dibatalkan',
                    'pesan' => "CST {$cst->cst_code} telah dibatalkan.",
                    'action' => 'Delete',
                    'is_read' => false,
                    'created_at' => now(),
                ]);
            }

            // Translog 
            $this->insertLog('Cancel CST', $cst->cst_code, $username);

            return response()->json([
                'success' => true,
                'message' => 'CST berhasil dibatalkan.'
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid action'], 400);
    }

    private function insertLog($action, $cst_code, $username) {
        $maxId = DB::table('tb_trans_swo_log')->max('id_trans_swo_log');
        DB::table('tb_trans_swo_log')->insert([
            'id_trans_swo_log' => $maxId ? $maxId + 1 : 1,
            'translog_date' => date('Y-m-d H:i:s'),
            'kode_trans' => $cst_code,
            'user_id' => $username,
            'action' => $action,
            'table_name' => 'tb_afs_cst',
            'form' => 'CST'
        ]);
    }
}
