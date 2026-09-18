<?php

namespace App\Http\Controllers\Leads;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Leads\LeadsHdr;
use App\Models\Leads\LeadsItem;
use App\Models\Leads\LeadsVisit;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LeadsController extends Controller
{
    public function index()
    {
        try {
            $leads = LeadsHdr::select('tb_leads_hdr.id', 'tb_leads_hdr.code_leads', 'm_customers.nm_customers', 'tb_leads_hdr.status')
                ->join('m_customers', 'tb_leads_hdr.id_customers', '=', 'm_customers.id_customers')
                ->orderBy('tb_leads_hdr.id', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil daftar Leads',
                'data' => $leads
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $header = LeadsHdr::select('tb_leads_hdr.id', 'tb_leads_hdr.id_customers', 'tb_leads_hdr.code_leads', 'm_customers.nm_customers', 'tb_leads_hdr.notes', 'tb_leads_hdr.kurs', 'm_customers.customers_address', 'tb_leads_hdr.status')
                ->join('m_customers', 'tb_leads_hdr.id_customers', '=', 'm_customers.id_customers')
                ->where('tb_leads_hdr.id', $id)
                ->first();

            if (!$header) {
                return response()->json([
                    'success' => false,
                    'message' => 'Leads tidak ditemukan',
                ], 404);
            }

            $items = LeadsItem::select('tb_leads_item.id', 'tb_leads_item.id_product', 'tb_leads_item.persentase', 'm_product.code_product', 'm_product.nm_product', 'm_product.product_deskripsi', 'm_product_satuan.nm_product_satuan', 'tb_leads_item.product_price', 'tb_leads_item.qty', DB::raw('(tb_leads_item.product_price * tb_leads_item.qty) as total'))
                ->join('m_product', 'tb_leads_item.id_product', '=', 'm_product.id_product')
                ->leftJoin('m_product_satuan', 'm_product.id_product_satuan', '=', 'm_product_satuan.id_product_satuan')
                ->where('tb_leads_item.lead_id', $id)
                ->get();

            $visits = LeadsVisit::where('lead_id', $id)->get();

            return response()->json([
                'success' => true,
                'message' => 'Berhasil mengambil detail Leads',
                'data' => [
                    'header' => $header,
                    'items' => $items,
                    'visits' => $visits,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_customers' => 'required',
            'notes' => 'nullable|string',
            'kurs' => 'nullable|numeric',
            'items' => 'nullable|array',
            'visits' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $year = Carbon::now()->format('Y');
            $month = Carbon::now()->format('m');
            $prefix = "LEADS-EMM/{$year}/{$month}/";
            
            $lastLead = LeadsHdr::where('code_leads', 'like', $prefix . '%')
                        ->orderBy('code_leads', 'desc')
                        ->first();
            
            if ($lastLead) {
                $lastSequence = (int) substr($lastLead->code_leads, -5);
                $newSequence = str_pad($lastSequence + 1, 5, '0', STR_PAD_LEFT);
            } else {
                $newSequence = '00001';
            }
            
            $code_leads = $prefix . $newSequence;

            $header = LeadsHdr::create([
                'code_leads' => $code_leads,
                'id_customers' => $request->id_customers,
                'notes' => $request->notes,
                'kurs' => $request->kurs ?? 1,
                'status' => 'DRAFT',
            ]);

            if ($request->has('items') && is_array($request->items)) {
                foreach ($request->items as $item) {
                    if (isset($item['id_product'])) {
                        LeadsItem::create([
                            'lead_id' => $header->id,
                            'id_product' => $item['id_product'],
                            'qty' => $item['qty'] ?? 0,
                            'product_price' => $item['product_price'] ?? 0,
                            'persentase' => $item['persentase'] ?? 0,
                        ]);
                    }
                }
            }

            if ($request->has('visits') && is_array($request->visits)) {
                foreach ($request->visits as $visit) {
                    if (isset($visit['date_visit'])) {
                        LeadsVisit::create([
                            'lead_id' => $header->id,
                            'date_visit' => Carbon::parse($visit['date_visit'])->format('Y-m-d'),
                            'visit_activity' => $visit['visit_activity'] ?? '',
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Leads berhasil disimpan',
                'data' => $header
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan leads: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $header = LeadsHdr::find($id);
        if (!$header) {
            return response()->json([
                'success' => false,
                'message' => 'Leads tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_customers' => 'required',
            'notes' => 'nullable|string',
            'items' => 'nullable|array',
            'visits' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $header->update([
                'id_customers' => $request->id_customers,
                'notes' => $request->notes,
            ]);

            if ($request->has('items')) {
                LeadsItem::where('lead_id', $id)->delete();
                foreach ($request->items as $item) {
                    if (isset($item['id_product'])) {
                        LeadsItem::create([
                            'lead_id' => $header->id,
                            'id_product' => $item['id_product'],
                            'qty' => $item['qty'] ?? 0,
                            'product_price' => $item['product_price'] ?? 0,
                            'persentase' => $item['persentase'] ?? 0,
                        ]);
                    }
                }
            }

            if ($request->has('visits')) {
                LeadsVisit::where('lead_id', $id)->delete();
                foreach ($request->visits as $visit) {
                    if (isset($visit['date_visit'])) {
                        LeadsVisit::create([
                            'lead_id' => $header->id,
                            'date_visit' => Carbon::parse($visit['date_visit'])->format('Y-m-d'),
                            'visit_activity' => $visit['visit_activity'] ?? '',
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Leads berhasil diperbarui',
                'data' => $header
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui leads: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $header = LeadsHdr::find($id);
        if (!$header) {
            return response()->json([
                'success' => false,
                'message' => 'Leads tidak ditemukan',
            ], 404);
        }

        DB::beginTransaction();
        try {
            LeadsItem::where('lead_id', $id)->delete();
            LeadsVisit::where('lead_id', $id)->delete();
            $header->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Leads berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus leads: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $header = LeadsHdr::find($id);
        if (!$header) {
            return response()->json([
                'success' => false,
                'message' => 'Leads tidak ditemukan',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $header->update([
                'status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Status Leads berhasil diperbarui',
                'data' => $header
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status: ' . $e->getMessage()
            ], 500);
        }
    }
}
