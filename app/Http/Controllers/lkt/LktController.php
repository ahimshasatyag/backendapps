<?php

namespace App\Http\Controllers\lkt;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\lkt\AfsLkt;

class LktController extends Controller
{
    public function index()
    {
        $lkts = DB::table('tb_afs_lkt as lkt')
            ->join('tb_afs_cst as cst', 'lkt.id_afs_cst', '=', 'cst.id_afs_cst')
            ->join('tb_afs_csr as csr', 'cst.id_afs_csr', '=', 'csr.id_afs_csr')
            ->leftJoin('tb_afs_realisasi as r', 'lkt.id_afs_lkt', '=', 'r.id_afs_lkt')
            ->leftJoin('m_customers as c', 'csr.id_customers', '=', 'c.id_customers')
            ->leftJoin('m_kota as kota', 'c.kabupaten', '=', 'kota.id')
            ->leftJoin('m_provinsi as prov', 'c.provinsi', '=', 'prov.id')
            ->select(
                'lkt.*',
                'lkt.id_afs_lkt as id',
                'cst.cst_code',
                'cst.cst_date',
                'csr.csr_code',
                'csr.waranty_end',
                'csr.lap_kerusakan',
                'c.nm_customers',
                'r.actual_starting_date',
                'r.actual_day',
                'r.actual_service_amount',
                'r.actual_transport_amount',
                'r.actual_accommodation_amount',
                'r.actual_description',
                'r.status as r_status',
                'r.actual_training',
                'r.actual_bongkar',
                'r.actual_transport as type_transport',
                'r.flag_daring as daring',
                'kota.nama_kabupaten as kabupaten_kota',
                'prov.nama as provinsi'
            )
            ->orderBy('lkt.id_afs_lkt', 'desc')
            ->get();

        // Group by id_afs_lkt to avoid duplicate rows from multiple realisasi
        $lkts = $lkts->groupBy('id_afs_lkt')->map(function ($group) {
            $first = $group->first();
            
            // Sum amounts across all realisasi
            $first->actual_service_amount = $group->sum('actual_service_amount');
            $first->actual_transport_amount = $group->sum('actual_transport_amount');
            $first->actual_accommodation_amount = $group->sum('actual_accommodation_amount');
            $first->actual_training = $group->sum('actual_training');
            $first->actual_bongkar = $group->sum('actual_bongkar');
            $first->actual_day = $group->sum('actual_day');

            // Use the most recent daring/transport from the latest realisasi
            $last = $group->last();
            $first->type_transport = $last->type_transport;
            $first->daring = $last->daring;

            return $first;
        })->values();

        foreach ($lkts as $lkt) {
            // Garansi logic
            if ($lkt->waranty_end && $lkt->cst_date) {
                $lkt->garansi = strtotime($lkt->cst_date) <= strtotime($lkt->waranty_end) ? 'GARANSI' : 'NON GARANSI';
            } else {
                $lkt->garansi = 'NON GARANSI';
            }
            
            // Age in
            if ($lkt->cst_date && $lkt->starting_date) {
                $diff = strtotime($lkt->starting_date) - strtotime($lkt->cst_date);
                $lkt->age_in = max(0, floor($diff / (60 * 60 * 24)));
            } else {
                $lkt->age_in = 0;
            }
            
            // Format daring
            if (is_null($lkt->daring)) {
                $lkt->daring = '-';
            } else {
                $lkt->daring = $lkt->daring == 1 ? 'DARING' : 'VISIT';
            }
        }

        return response()->json([
            'status' => true,
            'data' => $lkts
        ]);
    }

    /**
     * Get LKT detail with realisasi list
     */
    public function show($id)
    {
        $lkt_code = str_replace('.', '/', $id);

        $lkt = DB::table('tb_afs_lkt as lkt')
            ->join('tb_afs_cst as cst', 'lkt.id_afs_cst', '=', 'cst.id_afs_cst')
            ->join('tb_afs_csr as csr', 'cst.id_afs_csr', '=', 'csr.id_afs_csr')
            ->leftJoin('m_customers as c', 'csr.id_customers', '=', 'c.id_customers')
            ->leftJoin('m_product as p', 'csr.id_product', '=', 'p.id_product')
            ->leftJoin('m_karyawan as k', 'csr.id_karyawan', '=', 'k.id_karyawan')
            ->select(
                'lkt.*',
                'cst.cst_code',
                'cst.cst_date',
                'csr.csr_code',
                'csr.csr_date',
                'csr.lap_kerusakan',
                'csr.barcode',
                'csr.lokasi',
                'c.nm_customers',
                'c.customers_address',
                'p.nm_product',
                'p.code_product',
                'k.nm_karyawan'
            )
            ->where('lkt.lkt_code', $lkt_code)
            ->first();

        if (!$lkt) {
            return response()->json(['status' => false, 'message' => 'LKT tidak ditemukan'], 404);
        }
      
        // Get realisasi (visit) list
        $realisasi = DB::table('tb_afs_realisasi as r')
            ->where('r.id_afs_lkt', $lkt->id_afs_lkt)
            ->where('r.f_cancel', 0)
            ->orderBy('r.lkt_sub_code', 'asc')
            ->get();

        // For each realisasi, get teknisi list
        foreach ($realisasi as $r) {
            $r->teknisi_list = DB::table('tb_afs_realisasi_teknisi as rt')
                ->join('m_karyawan as k', 'rt.actual_id_karyawan', '=', 'k.id_karyawan')
                ->select('rt.*', 'k.nm_karyawan', 'rt.actual_id_karyawan as id_karyawan')
                ->where('rt.lkt_sub_code', $r->lkt_sub_code)
                ->where('rt.f_cancel', 0)
                ->get();

            // Get actual spareparts
            $r->parts = DB::table('tb_trans_swo_part_actual')
                ->where('id_visit', $r->lkt_sub_code)
                ->where('f_cancel', 0)
                ->select('name as nama_part', 'qty', 'harga')
                ->get();
        }

        $lkt->realisasi_list = $realisasi;

        // Get spareparts list
        $parts = DB::table('tb_trans_swo_part')
            ->where('lkt_code', $lkt_code)
            ->where('f_cancel', 0)
            ->select('name as nama_part', 'qty', 'harga')
            ->get();
        $lkt->parts = $parts;

        return response()->json(['status' => true, 'data' => $lkt]);
    }

    /**
     * Store new LKT
     */
    public function store(Request $request)
    {
        $cst_code = str_replace('.', '/', $request->cst_code ?? '');

        $cst = DB::table('tb_afs_cst')->where('cst_code', $cst_code)->first();
        if (!$cst) {
            return response()->json(['status' => false, 'message' => 'CST tidak ditemukan'], 404);
        }

        DB::beginTransaction();
        try {
            $link_foto = '';
            if ($request->hasFile('image')) {
                $file = $request->file('image');
                $filename = time() . '_' . $file->hashName();
                $file->move(public_path('assets/upload/afs/'), $filename);
                $link_foto = $filename;
            }

            // Generate LKT Code
            $today  = date('Y');
            $todayM = date('m');
            $maxLkt = DB::table('tb_afs_lkt')
                ->max(DB::raw("SUBSTRING(lkt_code, 17, 5)"));
            $noUrut   = (int) $maxLkt + 1;
            $lkt_code = 'LKT-EMM/' . $today . '/' . $todayM . '/' . sprintf('%05d', $noUrut);

            DB::table('tb_afs_lkt')->insert([
                'id_afs_cst'           => $cst->id_afs_cst,
                'lkt_code'             => $lkt_code,
                'starting_date'        => $request->starting_date ?? date('Y-m-d'),
                'estimation_day'       => $request->estimation_day ?? 1,
                'description'          => $request->description ?? '',
                'service_amount'       => (int) $request->service_amount ?? 0,
                'transport_amount'     => (int) $request->transport_amount ?? 0,
                'accommodation_amount' => (int) $request->accommodation_amount ?? 0,
                'tot_detail_amount'    => ((int) $request->service_amount ?? 0) + ((int) $request->transport_amount ?? 0) + ((int) $request->accommodation_amount ?? 0),
                'actual_transport'     => $request->type_transport ?? '',
                'image'                => $link_foto,
                'flag_done'            => 'Draft',
                'f_cancel'             => 0,
            ]);

            // Update CST status to ON PROGRESS
            DB::table('tb_afs_cst')->where('cst_code', $cst_code)->update(['status' => 'ON PROGRESS']);

            $this->insertLog('Tambah LKT', $lkt_code, $request->added_by ?? 'Admin');

            DB::commit();
            return response()->json(['status' => true, 'message' => 'LKT berhasil ditambahkan', 'lkt_code' => $lkt_code]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Gagal menyimpan LKT: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update LKT
     */
    public function update(Request $request, $id)
    {
        $lkt_code = str_replace('.', '/', $id);
        $lkt      = DB::table('tb_afs_lkt')->where('lkt_code', $lkt_code)->first();

        if (!$lkt) {
            return response()->json(['status' => false, 'message' => 'LKT tidak ditemukan'], 404);
        }

        $link_foto = $lkt->image;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->hashName();
            $file->move(public_path('assets/upload/afs/'), $filename);
            $link_foto = $filename;
        }

        DB::table('tb_afs_lkt')->where('lkt_code', $lkt_code)->update([
            'starting_date'        => $request->starting_date ?? $lkt->starting_date,
            'estimation_day'       => $request->estimation_day ?? $lkt->estimation_day,
            'description'          => $request->description ?? $lkt->description,
            'service_amount'       => (int) $request->service_amount ?? $lkt->service_amount,
            'transport_amount'     => (int) $request->transport_amount ?? $lkt->transport_amount,
            'accommodation_amount' => (int) $request->accommodation_amount ?? $lkt->accommodation_amount,
            'actual_transport'     => $request->type_transport ?? $lkt->actual_transport,
            'image'                => $link_foto,
        ]);

        $this->insertLog('Ubah LKT', $lkt_code, $request->updated_by ?? 'Admin');
        // insertNotification removed

        // Update BAST details in CSR if provided
        if ($request->has('no_bast') || $request->has('tgl_bast')) {
            $cst = DB::table('tb_afs_cst')->where('id_afs_cst', $lkt->id_afs_cst)->first();
            if ($cst) {
                $csrUpdateData = [];
                if ($request->has('no_bast')) {
                    $csrUpdateData['no_bast'] = $request->no_bast;
                }
                if ($request->has('tgl_bast')) {
                    $csrUpdateData['tlg_bast'] = $request->tgl_bast; // Column in db is tlg_bast
                }
                if (!empty($csrUpdateData)) {
                    DB::table('tb_afs_csr')->where('id_afs_csr', $cst->id_afs_csr)->update($csrUpdateData);
                }
            }
        }

        // Process Spareparts
        if ($request->has('parts') && is_array($request->parts)) {
            DB::table('tb_trans_swo_part')->where('lkt_code', $lkt_code)->delete();
            
            $partsData = [];
            foreach ($request->parts as $part) {
                $qty = isset($part['qty']) ? (int) $part['qty'] : 0;
                $harga = isset($part['harga']) ? (int) $part['harga'] : 0;
                $partsData[] = [
                    'lkt_code' => $lkt_code,
                    'name'     => $part['nama_part'] ?? '',
                    'qty'      => $qty,
                    'harga'    => $harga,
                    'total'    => $qty * $harga,
                    'f_cancel' => 0,
                ];
            }
            if (!empty($partsData)) {
                DB::table('tb_trans_swo_part')->insert($partsData);
            }
        }

        return response()->json(['status' => true, 'message' => 'LKT berhasil diupdate']);
    }

    /**
     * Done/Close LKT (set flag_done = DONE)
     */
    public function done(Request $request, $id)
    {
        $lkt_code = str_replace('.', '/', $id);
        $lkt      = DB::table('tb_afs_lkt')->where('lkt_code', $lkt_code)->first();

        if (!$lkt) {
            return response()->json(['status' => false, 'message' => 'LKT tidak ditemukan'], 404);
        }

        $link_bast = $lkt->bast;
        $flag_done = 'ON PROGRESS';

        if ($request->hasFile('bast')) {
            $file = $request->file('bast');
            $filename = time() . '_' . $file->hashName();
            $file->move(public_path('assets/upload/afs/'), $filename);
            $link_bast = $filename;
            $flag_done = 'CLOSE';
        }

        DB::table('tb_afs_lkt')->where('lkt_code', $lkt_code)->update([
            'flag_done'     => $flag_done,
            'bast'          => $link_bast,
            'lkt_done_date' => $flag_done === 'CLOSE' ? date('Y-m-d H:i:s') : $lkt->lkt_done_date,
        ]);

        $this->insertLog('Done LKT', $lkt_code, $request->done_by);
        
        if ($flag_done === 'CLOSE' || ($flag_done === 'ON PROGRESS' && $request->id_users_level == 17)) {
            $pesan = $flag_done === 'CLOSE' ? "LKT {$lkt_code} telah diselesaikan." : "LKT {$lkt_code} diproses (ON PROGRESS).";
            // insertNotification removed
        }

        return response()->json(['status' => true, 'message' => $flag_done === 'DONE' ? 'LKT berhasil di-Done' : 'LKT berhasil diproses (ON PROGRESS)']);
    }

    /**
     * Cancel LKT (cascade to CST and CSR)
     */
    public function cancel(Request $request, $id)
    {
        $lkt_code   = str_replace('.', '/', $id);
        $cancel_by  = $request->cancel_by ?? 'Admin';
        $cancel_date = date('Y-m-d H:i:s');

        $lkt = DB::table('tb_afs_lkt')->where('lkt_code', $lkt_code)->first();
        if (!$lkt) {
            return response()->json(['status' => false, 'message' => 'LKT tidak ditemukan'], 404);
        }

        DB::beginTransaction();
        try {
            // 1. Cancel LKT
            DB::table('tb_afs_lkt')->where('lkt_code', $lkt_code)->update([
                'f_cancel'        => 1,
                'lkt_cancel_date' => $cancel_date,
            ]);

            // 2. Cancel all realisasi for this LKT
            DB::table('tb_afs_realisasi')->where('id_afs_lkt', $lkt->id_afs_lkt)->update(['f_cancel' => 1]);

            // 3. Cancel realisasi_teknisi
            DB::table('tb_afs_realisasi_teknisi')->where('id_afs_lkt', $lkt->id_afs_lkt)->update(['f_cancel' => 1]);

            // 4. Get CST
            $cst = DB::table('tb_afs_cst')->where('id_afs_cst', $lkt->id_afs_cst)->first();
            if ($cst) {
                // 5. Cancel CST and reset CSR to OUTSTANDING
                DB::table('tb_afs_cst')->where('id_afs_cst', $cst->id_afs_cst)->update([
                    'status'          => 'CANCEL',
                    'ignore_cst_by'   => $cancel_by,
                    'cst_ignore_date' => $cancel_date,
                ]);

                DB::table('tb_afs_csr')->where('id_afs_csr', $cst->id_afs_csr)->update([
                    'csr_status' => 'OUTSTANDING',
                    'f_cancel'   => 0,
                ]);

                $this->insertLog('Cancel CST', $cst->cst_code, $cancel_by);
            }

            $this->insertLog('Cancel LKT', $lkt_code, $cancel_by);
            // insertNotification removed

            DB::commit();
            return response()->json(['status' => true, 'message' => 'LKT berhasil dibatalkan']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Gagal membatalkan: ' . $e->getMessage()], 500);
        }
    }

    // ===================== REALISASI (VISIT) =====================

    /**
     * Add a new visit/realisasi for an LKT
     */
    public function storeRealisasi(Request $request, $lkt_id)
    {
        $lkt_code = str_replace('.', '/', $lkt_id);
        $lkt      = DB::table('tb_afs_lkt')->where('lkt_code', $lkt_code)->first();

        if (!$lkt) {
            return response()->json(['status' => false, 'message' => 'LKT tidak ditemukan'], 404);
        }

        $actual_transport     = (int) ($request->actual_transport_amount ?? 0);
        $actual_accommodation = (int) ($request->actual_accommodation_amount ?? 0);
        $actual_service       = (int) ($request->actual_service_amount ?? 0);
        $actual_training      = (int) ($request->actual_training ?? 0);
        $actual_bongkar       = (int) ($request->actual_bongkar ?? 0);
        $tot                  = $actual_transport + $actual_accommodation + $actual_service + $actual_training + $actual_bongkar;

        $link_foto = '';
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->hashName();
            $file->move(public_path('assets/upload/afs/'), $filename);
            $link_foto = $filename;
        }

        DB::beginTransaction();
        try {
            $lkt_sub_code = DB::table('tb_afs_realisasi')->insertGetId([
                'id_afs_lkt'                  => $lkt->id_afs_lkt,
                'actual_starting_date'        => $request->actual_starting_date ?? date('Y-m-d'),
                'actual_day'                  => $request->actual_day ?? 1,
                'actual_description'          => $request->actual_description ?? '',
                'actual_service_amount'       => $actual_service,
                'actual_transport_amount'     => $actual_transport,
                'actual_accommodation_amount' => $actual_accommodation,
                'actual_training'             => $actual_training,
                'actual_bongkar'              => $actual_bongkar,
                'actual_tot_detail_amount'    => $tot,
                'flag_daring'                 => $request->flag_daring ? 1 : 0,
                'image'                       => $link_foto,
                'status'                      => 'Draft',
                'f_cancel'                    => 0,
            ]);

            // Insert teknisi list
            if ($request->teknisi_ids && is_array($request->teknisi_ids)) {
                foreach ($request->teknisi_ids as $id_karyawan) {
                    DB::table('tb_afs_realisasi_teknisi')->insert([
                        'lkt_sub_code'       => $lkt_sub_code,
                        'id_afs_cst'         => $lkt->id_afs_cst,
                        'id_afs_lkt'         => $lkt->id_afs_lkt,
                        'actual_id_karyawan' => $id_karyawan,
                        'f_cancel'           => 0,
                    ]);
                }
            }

            $this->insertLog('Tambah VISIT', $lkt_sub_code, $request->added_by);


            DB::commit();
            return response()->json(['status' => true, 'message' => 'Visit berhasil ditambahkan', 'lkt_sub_code' => $lkt_sub_code]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Gagal menyimpan visit: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update visit/realisasi
     */
    public function updateRealisasi(Request $request, $lkt_sub_code_encoded)
    {
        $lkt_sub_code = str_replace('.', '/', $lkt_sub_code_encoded);

        $actual_transport     = (int) ($request->actual_transport_amount ?? 0);
        $actual_accommodation = (int) ($request->actual_accommodation_amount ?? 0);
        $actual_service       = (int) ($request->actual_service_amount ?? 0);
        $actual_training      = (int) ($request->actual_training ?? 0);
        $actual_bongkar       = (int) ($request->actual_bongkar ?? 0);
        $tot                  = $actual_transport + $actual_accommodation + $actual_service + $actual_training + $actual_bongkar;

        $realisasi = DB::table('tb_afs_realisasi')->where('lkt_sub_code', $lkt_sub_code)->first();
        if (!$realisasi) {
            return response()->json(['status' => false, 'message' => 'Visit tidak ditemukan'], 404);
        }

        $link_foto = $realisasi->image;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->hashName();
            $file->move(public_path('assets/upload/afs/'), $filename);
            $link_foto = $filename;
        }

        DB::table('tb_afs_realisasi')->where('lkt_sub_code', $lkt_sub_code)->update([
            'actual_starting_date'        => $request->actual_starting_date,
            'actual_day'                  => $request->actual_day,
            'actual_description'          => $request->actual_description,
            'actual_service_amount'       => $actual_service,
            'actual_transport_amount'     => $actual_transport,
            'actual_accommodation_amount' => $actual_accommodation,
            'actual_training'             => $actual_training,
            'actual_bongkar'              => $actual_bongkar,
            'actual_tot_detail_amount'    => $tot,
            'flag_daring'                 => $request->flag_daring ? 1 : 0,
            'image'                       => $link_foto,
        ]);

        // Re-insert teknisi
        if ($realisasi) {
            $lkt = DB::table('tb_afs_lkt')->where('id_afs_lkt', $realisasi->id_afs_lkt)->first();
            DB::table('tb_afs_realisasi_teknisi')->where('lkt_sub_code', $lkt_sub_code)->delete();
            if ($request->teknisi_ids && is_array($request->teknisi_ids)) {
                foreach ($request->teknisi_ids as $id_karyawan) {
                    if ($id_karyawan && $id_karyawan !== 'null' && $id_karyawan !== 'undefined') {
                        DB::table('tb_afs_realisasi_teknisi')->insert([
                            'lkt_sub_code'       => $lkt_sub_code,
                            'id_afs_cst'         => $lkt ? $lkt->id_afs_cst : null,
                            'id_afs_lkt'         => $realisasi->id_afs_lkt,
                            'actual_id_karyawan' => $id_karyawan,
                            'f_cancel'           => 0,
                        ]);
                    }
                }
            }

            // Process Actual Spareparts
            if ($request->has('parts') && is_array($request->parts)) {
                DB::table('tb_trans_swo_part_actual')->where('id_visit', $lkt_sub_code)->delete();
                
                $actualPartsData = [];
                foreach ($request->parts as $part) {
                    $qty = isset($part['qty']) ? (int) $part['qty'] : 0;
                    $harga = isset($part['harga']) ? (int) $part['harga'] : 0;
                    $actualPartsData[] = [
                        'lkt_code' => $lkt ? $lkt->lkt_code : '0',
                        'id_visit' => $lkt_sub_code,
                        'name'     => $part['nama_part'] ?? '',
                        'qty'      => (string) $qty,
                        'harga'    => $harga,
                        'total'    => $qty * $harga,
                        'f_cancel' => 0,
                    ];
                }
                if (!empty($actualPartsData)) {
                    DB::table('tb_trans_swo_part_actual')->insert($actualPartsData);
                }
            }
        } 
        
        $this->insertLog('Ubah VISIT', $lkt_sub_code, $request->updated_by ?? 'Admin');

        return response()->json(['status' => true, 'message' => 'Visit berhasil diupdate']);
    }

    /**
     * Confirm a visit (status = ON PROGRESS)
     */
    public function confirmRealisasi(Request $request, $lkt_sub_code_encoded)
    {
        $lkt_sub_code = str_replace('.', '/', $lkt_sub_code_encoded);

        DB::table('tb_afs_realisasi')->where('lkt_sub_code', $lkt_sub_code)->update([
            'status' => 'ON PROGRESS',
        ]);

        $this->insertLog('Confirm VISIT', $lkt_sub_code, $request->confirmed_by ?? 'Admin');

        return response()->json(['status' => true, 'message' => 'Visit berhasil dikonfirmasi']);
    }

    /**
     * Close a visit (status = CLOSE)
     */
    public function closeRealisasi(Request $request, $lkt_sub_code_encoded)
    {
        $lkt_sub_code = str_replace('.', '/', $lkt_sub_code_encoded);

        DB::table('tb_afs_realisasi')->where('lkt_sub_code', $lkt_sub_code)->update([
            'status'         => 'CLOSE',
            'lap_penyelesain' => $request->lap_penyelesain ?? '',
        ]);

        $this->insertLog('Close VISIT', $lkt_sub_code, $request->done_by ?? 'Admin');

        return response()->json(['status' => true, 'message' => 'Visit berhasil di-Close']);
    }

    /**
     * Cancel a visit
     */
    public function cancelRealisasi(Request $request, $lkt_sub_code_encoded)
    {
        $lkt_sub_code = str_replace('.', '/', $lkt_sub_code_encoded);

        DB::table('tb_afs_realisasi')->where('lkt_sub_code', $lkt_sub_code)->update(['f_cancel' => 1]);
        DB::table('tb_afs_realisasi_teknisi')->where('lkt_sub_code', $lkt_sub_code)->update(['f_cancel' => 1]);

        $this->insertLog('Cancel VISIT', $lkt_sub_code, $request->cancel_by ?? 'Admin');

        return response()->json(['status' => true, 'message' => 'Visit berhasil dibatalkan']);
    }

    /**
     * Get available teknisi list
     */
    public function getTeknisiOptions()
    {
        $teknisi = DB::table('m_karyawan')
            ->select('id_karyawan', 'nm_karyawan')
            ->where('flag_status', 1)
            ->orderBy('nm_karyawan')
            ->get();

        return response()->json(['status' => true, 'data' => $teknisi]);
    }

    /**
     * Reject Close LKT
     */
    public function rejectLktRealisasi(Request $request, $id)
    {
        $lkt_sub_code = str_replace('.', '/', $id);
        $note = $request->input('note', '');

        $realisasi = DB::table('tb_afs_realisasi')->where('lkt_sub_code', $lkt_sub_code)->first();
        if (!$realisasi) {
            return response()->json(['status' => false, 'message' => 'LKT Realisasi not found'], 404);
        }

        DB::table('tb_afs_realisasi')->where('lkt_sub_code', $lkt_sub_code)->update([
            'status' => 'CANCEL',
            'alasan_cancel' => $note
        ]);

        $this->insertLog('Cancel LKT', $lkt_sub_code, $request->rejected_by ?? 'Admin');
        
        $reason = $request->input('reason', '');
        if ($reason === '') {
            $reason = $note;
        }

        // Get Technician Users
        $teknisiList = DB::table('tb_afs_realisasi_teknisi')
            ->where('lkt_sub_code', $lkt_sub_code)
            ->where('f_cancel', 0)
            ->get();

        $pesan = "Visit/Realisasi {$lkt_sub_code} telah ditolak. Alasan: {$reason}";
        $now = now();
        $insertData = [];

        foreach ($teknisiList as $teknisi) {
            $id_karyawan = $teknisi->actual_id_karyawan ?? $teknisi->id_karyawan;
            if ($id_karyawan) {
                $techUser = DB::table('m_users')->where('id_karyawan', $id_karyawan)->first();
                if ($techUser) {
                    $insertData[] = [
                        'user_id' => $techUser->id,
                        'id_users_level' => 17,
                        'kode_trans' => $lkt_sub_code,
                        'judul' => 'Visit Ditolak',
                        'pesan' => $pesan,
                        'action' => 'Delete',
                        'is_read' => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
        }

        return response()->json(['status' => true, 'message' => 'LKT Cancel successfully.']);
    }

    private function insertLog($action, $kode, $username)
    {
        try {
            $maxId = DB::table('tb_trans_swo_log')->max('id_trans_swo_log');
            DB::table('tb_trans_swo_log')->insert([
                'id_trans_swo_log' => $maxId ? $maxId + 1 : 1,
                'translog_date'    => date('Y-m-d H:i:s'),
                'kode_trans'       => $kode,
                'user_id'          => $username,
                'action'           => $action,
                'table_name'       => 'tb_afs_lkt',
                'form'             => 'LKT'
            ]);
        } catch (\Exception $e) {
            // Silently fail log
        }
    }

    private function getTargetTeknisi($lkt_code_or_sub)
    {
        $lkt_code = $lkt_code_or_sub;
        if (substr_count($lkt_code_or_sub, '/') >= 4) {
            $parts = explode('/', $lkt_code_or_sub);
            array_pop($parts);
            $lkt_code = implode('/', $parts);
        }
        $lkt = DB::table('tb_afs_lkt')->where('lkt_code', $lkt_code)->first();
        if ($lkt) {
            $teknisi_query = DB::table('tb_afs_realisasi_teknisi')
                ->where('f_cancel', 0);
                
            if (substr_count($lkt_code_or_sub, '/') >= 4) {
                // If it is a visit (lkt_sub_code), search exactly by lkt_sub_code
                $teknisi_query->where('lkt_sub_code', str_replace('.', '/', $lkt_code_or_sub));
            } else {
                // Fallback to searching by id_afs_lkt
                $teknisi_query->where('id_afs_lkt', $lkt->id_afs_lkt);
            }
            
            $teknisi = $teknisi_query->first();

            if ($teknisi) {
                $id_karyawan = $teknisi->actual_id_karyawan ?? $teknisi->id_karyawan;
                if ($id_karyawan) {
                    $user = DB::table('m_users')->where('id_karyawan', $id_karyawan)->first();
                    if ($user) {
                        return [
                            'user_id' => $user->id,
                            'id_users_level' => $user->id_users_level
                        ];
                    }
                }
            }
        }
        return null;
    }
}
