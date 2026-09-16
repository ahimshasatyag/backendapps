<?php

namespace App\Http\Controllers\approvebaru;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\approvebaru\Approvebaru;

class ApprovebaruController extends Controller
{
    /**
     * Menampilkan list data pending approval 
     */
    public function index(Request $request)
    {
        $data_approval = Approvebaru::getPendingApprovals();

        return response()->json([
            'status' => true,
            'data_approval' => $data_approval
        ]);
    }

    /**
     * Mengeksekusi aksi approve 
     */
    public function approvalApprove(Request $request)
    {
        $id_approval = $request->id_approval;
        $data = [
            'approver_id' => $request->header('x-id-user') ?? 1,
            'status' => 'approved',
            'action' => 'approved'
        ];

        $result = Approvebaru::updateApprovalStatus($id_approval, $data);

        if ($result) {
            return response()->json(['status' => 'success', 'message' => 'Approval berhasil disetujui']);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Gagal menyetujui approval']);
        }
    }

    /**
     * Mengeksekusi aksi reject
     */
    public function approvalReject(Request $request)
    {
        $id_approval = $request->id_approval;
        $rejection_reason = $request->rejection_reason;
        
        $data = [
            'approver_id' => $request->header('x-id-user') ?? 1,
            'status' => 'rejected',
            'action' => 'rejected',
            'rejection_reason' => $rejection_reason
        ];

        $result = Approvebaru::updateApprovalStatus($id_approval, $data);

        if ($result) {
            return response()->json(['status' => 'success', 'message' => 'Approval berhasil ditolak']);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Gagal menolak approval']);
        }
    }

    /**
     * Mendapatkan detail approval lengkap dengan SO
     */
    public function getApprovalDetails(Request $request)
    {
        $id_approval = $request->id_approval;
        $approval = Approvebaru::getApprovalById($id_approval);
        
        if (!$approval) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data approval tidak ditemukan'
            ]);
        }

        // Get related approvals
        $related_approvals = DB::table('m_approvals')
                                ->where('record_id', $approval->record_id)
                                ->where('table_name', $approval->table_name)
                                ->get();

        $so_data = null;
        if ($approval->table_name === 'tb_so_hdr') {
            $so_data = DB::table('tb_so_hdr as a')
                ->select(
                    'a.*',
                    'b.nm_karyawan as salesperson',
                    'c.nm_customers as customer_name',
                    'c.customers_address',
                    'c.customers_email',
                    'c.customers_phone',
                    'd.nm_type_pembayaran as payment_type',
                    'e.nm_cara_pembayaran as payment_method',
                    'f.nm_waktu_bayar as payment_time'
                )
                ->leftJoin('m_karyawan as b', 'a.id_karyawan', '=', 'b.id_karyawan')
                ->leftJoin('m_customers as c', 'a.id_customers', '=', 'c.id_customers')
                ->leftJoin('m_type_bayar_hdr as d', 'a.id_type_pembayaran', '=', 'd.id_type_pembayaran')
                ->leftJoin('m_type_bayar_dtl as e', 'a.id_cara_pembayaran', '=', 'e.id_cara_pembayaran')
                ->leftJoin('m_waktu_bayar as f', 'a.id_waktu_bayar', '=', 'f.id_waktu_bayar')
                ->where('a.id_so', $approval->record_id)
                ->first();

            if ($so_data) {
                $products = DB::table('tb_so_dtl as a')
                    ->select(
                        'a.*',
                        'b.code_product',
                        'b.nm_product',
                        'c.nm_product_satuan'
                    )
                    ->leftJoin('m_product as b', 'a.id_product', '=', 'b.id_product')
                    ->leftJoin('m_product_satuan as c', 'b.id_product_satuan', '=', 'c.id_product_satuan')
                    ->where('a.id_so', $approval->record_id)
                    ->get();
                $so_data->products = $products;
            }
        }

        $products_mapped = [];
        if ($so_data && isset($so_data->products)) {
            foreach ($so_data->products as $product) {
                $products_mapped[] = [
                    'code' => $product->code_product ?? '-',
                    'name' => $product->nm_product ?? '-',
                    'status' => $product->status_barang ?? '-',
                    'price' => number_format($product->product_price ?? 0),
                    'qty' => $product->nqty ?? 0,
                    'unit' => $product->nm_product_satuan ?? '-',
                    'delivery_term' => $product->delivery_term ?? '-',
                    'line_total' => number_format($product->ntot_product_price_netto ?? 0)
                ];
            }
        }

        $response_data = [
            'status' => 'success',
            'data' => [
                'approval_status' => $approval->status,
                'description' => $approval->description,
                'related_approvals' => $related_approvals,
                
                'code_so' => $so_data ? $so_data->code_so : null,
                'salesperson' => $so_data ? $so_data->salesperson : null,
                'delivery_to' => $so_data ? $so_data->customers_address : null,
                'customer_name' => $so_data ? $so_data->customer_name : null,
                'customer_address' => $so_data ? $so_data->customers_address : null,
                'customer_email' => $so_data ? $so_data->customers_email : null,
                'customer_phone' => $so_data ? $so_data->customers_phone : null,
                
                'date' => $so_data ? ($so_data->date_so ? date('d-m-Y', strtotime($so_data->date_so)) : null) : null,
                'estimated_delivery' => $so_data ? ($so_data->date_estimasi ? date('d-m-Y', strtotime($so_data->date_estimasi)) : null) : null,
                'currency' => $so_data ? $so_data->vcurrency : null,
                'exchange_rate' => $so_data ? $so_data->nkurs : null,
                'ppn' => $so_data ? ($so_data->flag_ppn ? 'Ya' : 'Tidak') : null,
                'delivery_term' => $so_data ? $so_data->delivery_term : null,
                'freight_cost' => $so_data ? number_format($so_data->freight_amount ?? 0) : null,
                'technician_cost' => $so_data ? number_format($so_data->teknisi_amount ?? 0) : null,
                'forklift_cost' => $so_data ? number_format($so_data->forklift_amount ?? 0) : null,
                
                'payment_method' => $so_data ? $so_data->payment_method : null,
                'dp_percentage' => $so_data ? $so_data->ndp_persen : null,
                'tenor' => $so_data ? $so_data->ntenor : null,
                'dp_amount' => $so_data ? number_format($so_data->ndp_amount ?? 0) : null,
                'installment_amount' => $so_data ? number_format($so_data->ntenor_amount ?? 0) : null,
                'payment_type' => $so_data ? $so_data->payment_type : null,
                'payment_time' => $so_data ? $so_data->payment_time : null,
                
                'notes' => $so_data ? $so_data->keterangan : null,
                'so_excel_code' => $so_data ? $so_data->code_so_excel : null,
                'customer_po' => $so_data ? $so_data->no_po_cust : null,
                'success_fee' => $so_data ? number_format($so_data->success_fee ?? 0) : null,
                'internal_notes' => $so_data ? $so_data->internal_notes : null,
                
                'products' => $products_mapped,
                
                'total_amount' => $so_data ? number_format($so_data->ntot_price_netto_amount ?? 0) : null,
            ]
        ];

        return response()->json($response_data);
    }
}
