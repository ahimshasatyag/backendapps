<?php

namespace App\Http\Controllers\survey;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurveyController extends Controller
{
    /**
     * Get list of Survey
     * Mirip dengan Cform::data_table()
     */
    public function index(Request $request)
    {
        $query = DB::table('tb_survey as a')
            ->join('m_customers as b', 'a.id_customers', '=', 'b.id_customers')
            ->select(
                'a.id_survey',
                'a.code_survey',
                'a.date_request',
                'b.nm_customers',
                'a.survey_status'
            );

        // Search filter
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $terms = explode(',', $search);
                foreach ($terms as $term) {
                    $term = trim($term);
                    $q->orWhere('a.code_survey', 'like', "%$term%")
                      ->orWhere('b.nm_customers', 'like', "%$term%")
                      ->orWhere('a.survey_status', 'like', "%$term%");
                }
            });
        }

        $surveys = $query->orderBy('a.id_survey', 'desc')->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'data'   => $surveys
        ]);
    }

    /**
     * Support data for add form
     * Mirip dengan Cform::tambah_q() and related methods
     */
    public function supportData(Request $request)
    {
        $id_so = $request->id_so;

        $data_karyawan = DB::table('m_karyawan')->get();
        $data_survey_jenis = DB::table('m_survey_jenis')->get();
        
        $data_header_so = null;
        $data_customers_contact = null;
        $data_detail_so = null;
        
        if ($id_so) {
            $data_header_so = DB::table('tb_so_hdr as a')
                ->join('m_customers as b', 'a.id_customers', '=', 'b.id_customers')
                ->select('a.id_so', 'a.id_customers', 'b.nm_customers', 'b.customers_address')
                ->where('a.id_so', $id_so)
                ->first();

            if ($data_header_so) {
                $data_customers_contact = DB::table('m_customers_contact')
                    ->where('id_customers', $data_header_so->id_customers)
                    ->get();
            }

            $data_detail_so = DB::table('tb_so_dtl as a')
                ->join('m_product as b', 'a.id_product', '=', 'b.id_product')
                ->select('a.id_so_dtl', 'a.id_so', 'a.id_product', 'b.code_product', 'b.nm_product')
                ->where('a.id_so', $id_so)
                ->get();
        }

        return response()->json([
            'status' => true,
            'data_karyawan' => $data_karyawan,
            'data_survey_jenis' => $data_survey_jenis,
            'data_header_so' => $data_header_so,
            'data_customers_contact' => $data_customers_contact,
            'data_detail_so' => $data_detail_so,
        ]);
    }

    /**
     * Get detail survey
     * Mirip dengan Cform::edit() / mmaster->data_header() dll.
     */
    public function show($id)
    {
        $data_header = DB::table('tb_survey as a')
            ->join('m_karyawan as b', 'a.id_karyawan', '=', 'b.id_karyawan')
            ->join('m_karyawan_divisi as c', 'b.id_karyawan_divisi', '=', 'c.id_karyawan_divisi')
            ->join('m_customers as d', 'a.id_customers', '=', 'd.id_customers')
            ->leftJoin('m_customers_contact as e', 'a.id_customers_contact', '=', 'e.id_customers_contact')
            ->select(
                'a.id_survey',
                'a.code_survey',
                'a.id_karyawan',
                'c.nm_karyawan_divisi',
                'a.date_request',
                'a.id_customers',
                'd.nm_customers',
                'a.id_customers_contact',
                'a.pelaksana_afs',
                'd.customers_address',
                'e.customers_contact_mobile',
                'a.survey_status',
                'a.date_pelaksana',
                'a.note_survey',
                'b.nm_karyawan',
                'e.nm_customers_contact',
                'a.file_hasil_survey',
                'a.id_so'
            )
            ->where('a.id_survey', $id)
            ->first();

        if (!$data_header) {
            return response()->json(['status' => false, 'message' => 'Survey not found'], 404);
        }

        $data_detail_product = DB::table('tb_survey_dtl_product as a')
            ->join('m_product as b', 'a.id_product', '=', 'b.id_product')
            ->select(
                'a.id_survey_dtl_product',
                'a.id_survey',
                'a.id_product',
                'b.code_product',
                'b.nm_product',
                'a.product_berat'
            )
            ->where('a.id_survey', $id)
            ->get();

        $data_detail_jenis = DB::table('tb_survey_dtl_jenis')
            ->select('id_survey_jenis')
            ->where('id_survey', $id)
            ->get();

        $data_detail_tujuan = DB::table('tb_survey_dtl_tujuan')
            ->where('id_survey', $id)
            ->get();

        $data_detail_biaya = DB::table('tb_survey_dtl_biaya')
            ->where('id_survey', $id)
            ->get();

        $data_detail_pelaksana = DB::table('tb_survey_dtl_pelaksana as a')
            ->join('m_karyawan as b', 'a.id_karyawan', '=', 'b.id_karyawan')
            ->select(
                'a.id_survey_dtl_pelaksana',
                'a.id_survey',
                'a.id_karyawan',
                'b.nm_karyawan',
                'a.divisi'
            )
            ->where('a.id_survey', $id)
            ->get();
            
        $data_customers_contact = DB::table('m_customers_contact')
            ->where('id_customers', $data_header->id_customers)
            ->get();

        return response()->json([
            'status' => true,
            'data_header' => $data_header,
            'data_detail_product' => $data_detail_product,
            'data_detail_jenis' => $data_detail_jenis,
            'data_detail_tujuan' => $data_detail_tujuan,
            'data_detail_biaya' => $data_detail_biaya,
            'data_detail_pelaksana' => $data_detail_pelaksana,
            'data_customers_contact' => $data_customers_contact,
        ]);
    }

    /**
     * Store new survey
     * Mirip dengan Cform::simpan()
     */
    public function store(Request $request)
    {
        $request->validate([
            'id_karyawan' => 'required',
            'id_customers' => 'required',
            'id_customers_contact' => 'required',
            'id_survey_jenis' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $id_so = $request->id_so;
            $id_karyawan = $request->id_karyawan;
            $id_customers = $request->id_customers;
            $id_customers_contact = $request->id_customers_contact;
            $pelaksana_survey = $request->pelaksana_survey;
            $data_id_survey_jenis = $request->id_survey_jenis;
            $date_request = $request->date_request ? date("Y-m-d", strtotime($request->date_request)) : null;
            $periode = date("Ym", strtotime($request->date_request));
            
            $pelaksana_afs = false;
            if ($pelaksana_survey != null) {
                $pelaksana_afs = true;
            }

            $code_survey = $this->generateRunningNumber('RS', $periode);

            $id_survey = DB::table('tb_survey')->insertGetId([
                'code_survey' => $code_survey,
                'id_karyawan' => $id_karyawan,
                'id_customers' => $id_customers,
                'id_customers_contact' => $id_customers_contact,
                'pelaksana_gudang' => true,
                'pelaksana_afs' => $pelaksana_afs,
                'date_request' => $date_request,
                'date_create' => now(),
                'username_create' => $request->username ?? auth()->user()->username ?? 'system',
                'survey_status' => 'DRAFT',
                'id_so' => $id_so
            ]);

            if ($data_id_survey_jenis) {
                foreach ($data_id_survey_jenis as $id_survey_jenis) {
                    DB::table('tb_survey_dtl_jenis')->insert([
                        'id_survey' => $id_survey,
                        'id_survey_jenis' => $id_survey_jenis,
                    ]);
                }
            }

            $jml_barang = $request->jml_barang;
            if ($jml_barang) {
                for ($x = 0; $x <= $jml_barang; $x++) {
                    $id_product = $request->input('id_product' . $x);
                    $product_berat = $request->input('product_berat' . $x);

                    if ($id_product) {
                        DB::table('tb_survey_dtl_product')->insert([
                            'id_survey' => $id_survey,
                            'id_product' => $id_product,
                            'product_berat' => $product_berat ?? 0
                        ]);
                    }
                }
            }

            $jml_tujuan_survey = $request->jml_tujuan_survey;
            if ($jml_tujuan_survey) {
                for ($x = 0; $x <= $jml_tujuan_survey; $x++) {
                    $tujuan_survey = $request->input('tujuan_survey' . $x);

                    if ($tujuan_survey) {
                        DB::table('tb_survey_dtl_tujuan')->insert([
                            'id_survey' => $id_survey,
                            'tujuan_survey' => $tujuan_survey,
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => true, 'kode' => $code_survey, 'id_survey' => $id_survey, 'message' => 'Survey saved successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update survey
     * Mirip dengan Cform::update()
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'id_karyawan' => 'required',
            'id_customers' => 'required',
            'id_customers_contact' => 'required',
            'id_survey_jenis' => 'required|array',
        ]);

        DB::beginTransaction();
        try {
            $id_survey = $id;
            $code_survey = $request->code_survey;
            $id_karyawan = $request->id_karyawan;
            $id_customers = $request->id_customers;
            $id_customers_contact = $request->id_customers_contact;
            $pelaksana_survey = $request->pelaksana_survey;
            $data_id_survey_jenis = $request->id_survey_jenis;
            $date_request = $request->date_request ? date("Y-m-d", strtotime($request->date_request)) : null;
            
            $pelaksana_afs = false;
            if ($pelaksana_survey != null) {
                $pelaksana_afs = true;
            }

            DB::table('tb_survey')
                ->where('id_survey', $id_survey)
                ->update([
                    'id_karyawan' => $id_karyawan,
                    'id_customers' => $id_customers,
                    'id_customers_contact' => $id_customers_contact,
                    'pelaksana_gudang' => true,
                    'pelaksana_afs' => $pelaksana_afs,
                    'date_update' => now(),
                    'username_update' => $request->username ?? auth()->user()->username ?? 'system',
                    'survey_status' => 'DRAFT',
                    'date_request' => $date_request
                ]);

            DB::table('tb_survey_dtl_jenis')->where('id_survey', $id_survey)->delete();
            DB::table('tb_survey_dtl_tujuan')->where('id_survey', $id_survey)->delete();
            DB::table('tb_survey_dtl_product')->where('id_survey', $id_survey)->delete();

            if ($data_id_survey_jenis) {
                foreach ($data_id_survey_jenis as $id_survey_jenis) {
                    DB::table('tb_survey_dtl_jenis')->insert([
                        'id_survey' => $id_survey,
                        'id_survey_jenis' => $id_survey_jenis,
                    ]);
                }
            }

            $jml_barang = $request->jml_barang;
            if ($jml_barang) {
                for ($x = 0; $x <= $jml_barang; $x++) {
                    $id_product = $request->input('id_product' . $x);
                    $product_berat = $request->input('product_berat' . $x);

                    if ($id_product) {
                        DB::table('tb_survey_dtl_product')->insert([
                            'id_survey' => $id_survey,
                            'id_product' => $id_product,
                            'product_berat' => $product_berat ?? 0
                        ]);
                    }
                }
            }

            $jml_tujuan_survey = $request->jml_tujuan_survey;
            if ($jml_tujuan_survey) {
                for ($x = 0; $x <= $jml_tujuan_survey; $x++) {
                    $tujuan_survey = $request->input('tujuan_survey' . $x);

                    if ($tujuan_survey) {
                        DB::table('tb_survey_dtl_tujuan')->insert([
                            'id_survey' => $id_survey,
                            'tujuan_survey' => $tujuan_survey,
                        ]);
                    }
                }
            }

            DB::commit();
            return response()->json(['status' => true, 'kode' => $code_survey, 'id_survey' => $id_survey, 'message' => 'Survey updated successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Cancel Survey
     * Mirip dengan Cform::cancel_survey()
     */
    public function cancel(Request $request, $id)
    {
        DB::table('tb_survey')->where('id_survey', $id)->update(['survey_status' => 'CANCEL']);
        return response()->json(['status' => true, 'message' => 'Survey cancelled']);
    }

    /**
     * Confirm Survey
     * Mirip dengan Cform::confirm_survey()
     */
    public function confirm(Request $request, $id)
    {
        $data_header = DB::table('tb_survey')->where('id_survey', $id)->first();
        
        $survey_status = "WAITING APPROVAL MANAGER WAREHOUSE";
        if ($data_header->pelaksana_afs == 1 || $data_header->pelaksana_afs == true) {
            $survey_status = "WAITING APPROVAL MANAGER AFS";
        }

        DB::table('tb_survey')->where('id_survey', $id)->update(['survey_status' => $survey_status]);
        return response()->json(['status' => true, 'message' => $survey_status]);
    }
    
    /**
     * Update AFS
     * Mirip dengan Cform::update_afs()
     */
    public function updateAfs(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $divisi = $request->divisi;
            $jml_pelaksana_survey = $request->jml_pelaksana_survey;
            
            DB::table('tb_survey_dtl_pelaksana')
                ->where('id_survey', $id)
                ->where('divisi', $divisi)
                ->delete();

            if ($jml_pelaksana_survey) {
                for ($x = 0; $x <= $jml_pelaksana_survey; $x++) {
                    $id_karyawan_pelaksana = $request->input('id_karyawan_pelaksana' . $x);

                    if ($id_karyawan_pelaksana) {
                        DB::table('tb_survey_dtl_pelaksana')->insert([
                            'id_survey' => $id,
                            'id_karyawan' => $id_karyawan_pelaksana,
                            'divisi' => $divisi
                        ]);
                    }
                }
            }
            
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Pelaksana AFS updated']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update Gudang
     * Mirip dengan Cform::update_gudang()
     */
    public function updateGudang(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $date_pelaksana = $request->date_pelaksana ? date("Y-m-d", strtotime($request->date_pelaksana)) : null;
            $divisi = $request->divisi;
            $note_survey = $request->note_survey;
            
            DB::table('tb_survey')->where('id_survey', $id)->update([
                'date_pelaksana' => $date_pelaksana,
                'note_survey' => $note_survey
            ]);

            DB::table('tb_survey_dtl_pelaksana')->where('id_survey', $id)->where('divisi', $divisi)->delete();
            DB::table('tb_survey_dtl_jenis')->where('id_survey', $id)->delete();
            DB::table('tb_survey_dtl_biaya')->where('id_survey', $id)->delete();

            $data_id_survey_jenis = $request->id_survey_jenis;
            if ($data_id_survey_jenis) {
                foreach ($data_id_survey_jenis as $id_survey_jenis) {
                    DB::table('tb_survey_dtl_jenis')->insert([
                        'id_survey' => $id,
                        'id_survey_jenis' => $id_survey_jenis,
                    ]);
                }
            }

            $jml_pelaksana_survey = $request->jml_pelaksana_survey;
            if ($jml_pelaksana_survey) {
                for ($x = 0; $x <= $jml_pelaksana_survey; $x++) {
                    $id_karyawan_pelaksana = $request->input('id_karyawan_pelaksana' . $x);

                    if ($id_karyawan_pelaksana) {
                        DB::table('tb_survey_dtl_pelaksana')->insert([
                            'id_survey' => $id,
                            'id_karyawan' => $id_karyawan_pelaksana,
                            'divisi' => $divisi
                        ]);
                    }
                }
            }

            $jml_rincian_biaya = $request->jml_rincian_biaya;
            if ($jml_rincian_biaya) {
                for ($x = 0; $x <= $jml_rincian_biaya; $x++) {
                    $nominal_biaya = str_replace(',', '', $request->input('nominal_biaya' . $x));

                    if ($nominal_biaya) {
                        $rincian_biaya = $request->input('rincian_biaya' . $x);
                        DB::table('tb_survey_dtl_biaya')->insert([
                            'id_survey' => $id,
                            'nominal_biaya' => $nominal_biaya,
                            'rincian_biaya' => $rincian_biaya
                        ]);
                    }
                }
            }
            
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Survey Gudang updated']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update Progress
     * Mirip dengan Cform::update_progress()
     */
    public function updateProgress(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            DB::table('tb_survey_dtl_biaya')->where('id_survey', $id)->delete();

            $jml_rincian_biaya = $request->jml_rincian_biaya;
            if ($jml_rincian_biaya) {
                for ($x = 0; $x <= $jml_rincian_biaya; $x++) {
                    $nominal_biaya = str_replace(',', '', $request->input('nominal_biaya' . $x));

                    if ($nominal_biaya) {
                        $rincian_biaya = $request->input('rincian_biaya' . $x);
                        DB::table('tb_survey_dtl_biaya')->insert([
                            'id_survey' => $id,
                            'nominal_biaya' => $nominal_biaya,
                            'rincian_biaya' => $rincian_biaya
                        ]);
                    }
                }
            }
            
            DB::commit();
            return response()->json(['status' => true, 'message' => 'Survey Progress updated']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Approve AFS
     * Mirip dengan Cform::approve_afs()
     */
    public function approveAfs(Request $request, $id)
    {
        $id_approve = $request->id_approve;
        $survey_status = "WAITING APPROVAL MANAGER WAREHOUSE";

        if ($id_approve == 0 || $id_approve == '' || $id_approve == '0') {
            DB::table('tb_survey_dtl_pelaksana')
                ->where('id_survey', $id)
                ->where('divisi', 'AFS')
                ->delete();
                
            DB::table('tb_survey')->where('id_survey', $id)->update(['pelaksana_afs' => 0]);
        }

        DB::table('tb_survey')->where('id_survey', $id)->update(['survey_status' => $survey_status]);
        return response()->json(['status' => true, 'message' => $survey_status]);
    }

    /**
     * Approve Gudang
     * Mirip dengan Cform::approve_survey_gudang()
     */
    public function approveGudang(Request $request, $id)
    {
        $survey_status = "PROGRESS";
        DB::table('tb_survey')->where('id_survey', $id)->update(['survey_status' => $survey_status]);
        return response()->json(['status' => true, 'message' => $survey_status]);
    }

    /**
     * Selesai
     * Mirip dengan Cform::selesai()
     */
    public function selesai(Request $request, $id)
    {
        $survey_status = "DONE";
        DB::table('tb_survey')->where('id_survey', $id)->update(['survey_status' => $survey_status]);
        return response()->json(['status' => true, 'message' => $survey_status]);
    }

    /**
     * Upload Survey
     * Mirip dengan Cform::upload_survey()
     */
    public function upload(Request $request, $id)
    {
        $request->validate([
            'file' => 'required|mimes:jpg,png,jpeg'
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . rand(1000, 9999) . '.' . $file->getClientOriginalExtension();
            // Moving to public/assets/upload as in CI config
            $file->move(public_path('assets/upload'), $filename);

            DB::table('tb_survey')->where('id_survey', $id)->update(['file_hasil_survey' => $filename]);

            return response()->json(['status' => true, 'name' => $filename]);
        }

        return response()->json(['status' => false, 'message' => 'No file uploaded']);
    }
    
    /**
     * Internal function to generate running number
     */
    private function generateRunningNumber($prefix, $periode)
    {
        $prefix = $prefix . '-' . $periode . '-';
        $last_data = DB::table('tb_survey')
            ->where('code_survey', 'like', $prefix . '%')
            ->orderBy('code_survey', 'desc')
            ->first();
            
        if ($last_data) {
            $last_number = intval(substr($last_data->code_survey, -4));
            $new_number = $last_number + 1;
        } else {
            $new_number = 1;
        }
        
        return $prefix . str_pad($new_number, 4, '0', STR_PAD_LEFT);
    }
}
