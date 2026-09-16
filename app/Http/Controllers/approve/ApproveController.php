<?php

namespace App\Http\Controllers\approve;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\approve\Approve;

class ApproveController extends Controller
{
    /**
     * Menampilkan list data approval 
     */
    public function index(Request $request)
    {
        // Default ke level 1 jika header tidak dikirim
        $id_users_level = $request->header('x-id-users-level') ?? 1; 
        $search = $request->search;

        $data_quotations = Approve::getQuotations($id_users_level, $search);
        $data_accounting = Approve::getAccounting($id_users_level, $search);
        $data_history = Approve::getHistory($id_users_level, $search);

        return response()->json([
            'status' => true,
            'data_quotations' => $data_quotations,
            'data_accounting' => $data_accounting,
            'data_history' => $data_history,
            'search' => $search
        ]);
    }

    /**
     * Mengeksekusi aksi approve 
     */
    public function approve(Request $request)
    {
        $id_approval = $request->id_approval;
        $status = $request->status;
        $aksi = $request->aksi;
        $username = $request->header('x-username') ?? 'admin';

        DB::beginTransaction();
        try {
            $cek = Approve::processApprove($id_approval, $status, $aksi, $username);

            if ($cek) {
                $data_approval = Approve::getApproval($id_approval);
                $id_approve = $data_approval->id_approve;

                // Logika khusus id_approve 8, 9, 10
                if (in_array($id_approve, [8, 9, 10])) {
                    $id_invoice_dtl = $data_approval->id_key_table;
                    $data_invoice_dtl = DB::table('tb_invoice_dtl')->where('id_invoice_dtl', $id_invoice_dtl)->first();
                    
                    if ($data_invoice_dtl) {
                        $id_invoice = $data_invoice_dtl->id_invoice;
                        $v_amount = $data_invoice_dtl->v_amount;

                        // tambah_balance
                        DB::table('tb_invoice_hdr')->where('id_invoice', $id_invoice)->increment('ntot_balance', $v_amount);

                        // tambah_sisa_retur
                        if ($id_approve == 10) {
                            DB::table('tb_retur_penjualan_hdr')->where('id', $data_invoice_dtl->retur_penjualan_id)->increment('sisa', $v_amount);
                        }

                        // hapus_tanggal_cairdll
                        DB::table('tb_invoice_dtl')->where('id_invoice_dtl', $id_invoice_dtl)->update([
                            'date_tolak' => null,
                            'date_cair' => null,
                            'date_setor' => null,
                            'date_terima' => null,
                            'date_update' => now()
                        ]);

                        // ganti_status_invoice
                        DB::table('tb_invoice_hdr')->where('id_invoice', $id_invoice)->update([
                            'status_invoice' => 'OPEN'
                        ]);
                    }
                }
                
                DB::commit();
                return response()->json(['status' => true, 'message' => 'Approve berhasil']);
            } else {
                DB::rollBack();
                return response()->json(['status' => false, 'message' => 'Data approval tidak ditemukan']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Menampilkan detail untuk modal 
     */
    public function detail(Request $request)
    {
        $id_approval = $request->id_approval;
        $data_approval = Approve::getApproval($id_approval);
        
        if ($data_approval) {
            $id_menu = $data_approval->id_menu;
            $id_key_table = $data_approval->id_key_table;
            
            $nm_folder = DB::table('m_menu')->where('id_menu', $id_menu)->value('nm_folder');

            return response()->json([
                'status' => true,
                'nm_folder' => $nm_folder,
                'id_key_table' => $id_key_table
            ]);
        }
        
        return response()->json(['status' => false, 'message' => 'Not found'], 404);
    }
    
    /**
     * 
     */
    public function approvalApprove(Request $request)
    {
        $id_approval = $request->id_approval;
        $approver_id = $request->header('x-id-user') ?? 1;

        DB::table('m_approvals')->where('id', $id_approval)->update([
            'approver_id' => $approver_id,
            'status' => 'approved',
            'action' => 'approved'
        ]);

        return response()->json(['status' => 'success', 'message' => 'Approval berhasil disetujui']);
    }

    /**
     * 
     */
    public function approvalReject(Request $request)
    {
        $id_approval = $request->id_approval;
        $rejection_reason = $request->rejection_reason;
        $approver_id = $request->header('x-id-user') ?? 1;

        DB::table('m_approvals')->where('id', $id_approval)->update([
            'approver_id' => $approver_id,
            'status' => 'rejected',
            'action' => 'rejected',
            'rejection_reason' => $rejection_reason
        ]);

        return response()->json(['status' => 'success', 'message' => 'Approval berhasil ditolak']);
    }

    /**
     * (mirip approval_ignore pada Cform)
     */
    public function approvalIgnore(Request $request)
    {
        $id_approval = $request->id_approval;
        $approver_id = $request->header('x-id-user') ?? 1;

        DB::table('m_approvals')->where('id', $id_approval)->update([
            'approver_id' => $approver_id,
            'status' => 'passed',
            'action' => 'passed'
        ]);

        return response()->json(['status' => 'success', 'message' => 'Approval berhasil dilewati']);
    }
    
    /**
     * (approval_request berisi logic notifikasi Whatsapp)
     */
    public function approvalRequest(Request $request)
    {
        $id_approval = $request->id_approval;
        
        DB::table('m_approvals')->where('id', $id_approval)->update([
            'f_request_approval' => true
        ]);
        
        $data_approval = DB::table('m_approvals')->where('id', $id_approval)->first();
        if ($data_approval) {
            $module_name = $data_approval->module_name;
            $record_id = $data_approval->record_id;
            
            $pesan = "";
            $nomor_tujuan = ['6282226076210']; // Nomor backup default

            if (in_array($module_name, ['SO_001', 'SO_002', 'SO_003'])) {
                $data_header_so = DB::table('tb_so_hdr as a')
                    ->leftJoin('m_customers as c', 'a.id_customers', '=', 'c.id_customers')
                    ->leftJoin('m_karyawan as d', 'a.id_karyawan', '=', 'd.id_karyawan')
                    ->leftJoin('m_type_bayar_hdr as e', 'a.id_type_pembayaran', '=', 'e.id_type_pembayaran')
                    ->leftJoin('m_waktu_bayar as g', 'a.id_waktu_bayar', '=', 'g.id_waktu_bayar')
                    ->select('a.*', 'c.nm_customers', 'c.customers_address', 'd.nm_karyawan', 'e.nm_type_pembayaran', 'g.nm_waktu_bayar')
                    ->where('a.id_so', $record_id)
                    ->first();
                
                if ($data_header_so) {
                    $pesan = ($module_name == 'SO_001') ? "*APPROVAL QUOTATION SUCCESS FEE*\n" : "*APPROVAL QUOTATION DISKON*\n";
                    $pesan .= "APPROVAL NO : $id_approval \nNO SO : $data_header_so->code_so \nSales : $data_header_so->nm_karyawan \n";
                    $pesan .= "Customer : $data_header_so->nm_customers \nAPPROVE ?\nKetik 1 (Yes)\nKetik 0 (No)\n" . date('d-m-Y H:i:s');
                }

                $nomor_tujuan[] = ($module_name == 'SO_001' || $module_name == 'SO_003') ? '62816777535' : '628129807099';
                
            } else if ($module_name == 'SO_004') {
                $data_header_so = DB::table('tb_so_hdr as a')
                    ->leftJoin('m_customers as c', 'a.id_customers', '=', 'c.id_customers')
                    ->leftJoin('m_karyawan as d', 'a.id_karyawan', '=', 'd.id_karyawan')
                    ->select('a.*', 'c.nm_customers', 'c.customers_address', 'd.nm_karyawan')
                    ->where('a.id_so', $record_id)
                    ->first();

                if ($data_header_so) {
                    $pesan = "*APPROVAL QUOTATION CANCELED*\nAPPROVAL NO : $id_approval \nNO SO : $data_header_so->code_so \n";
                    $pesan .= "Sales : $data_header_so->nm_karyawan \nCustomer : $data_header_so->nm_customers \n";
                    $pesan .= "APPROVE ?\nKetik 1 (Yes)\nKetik 0 (No)\n" . date('d-m-Y H:i:s');
                }
                
                $nomor_tujuan[] = '62816777535';
            }

            // Insert into WhatsApp message pending list
            if ($pesan != "") {
                foreach ($nomor_tujuan as $no) {
                    DB::table('m_whatsapp_message_pending')->insert([
                        'data' => json_encode(['message' => $pesan, 'to' => $no]),
                        'date' => now(),
                        'status' => 'Pending'
                    ]);
                }
            }
        }

        return response()->json(['status' => 'success', 'message' => 'Approval request updated successfully']);
    }
}
