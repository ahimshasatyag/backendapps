<?php

namespace App\Http\Controllers\akt_kasbankin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\akt_kasbankin\AktKasbankin;

class AktKasbankinController extends Controller
{
    /**
     * Menampilkan data list KB Masuk
     */
    public function index(Request $request)
    {
        $perPage = $request->per_page ?? 10;
        $search = $request->search;
        
        $data = AktKasbankin::getList($perPage, $search);
        
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Mendapatkan data pendukung untuk form tambah (bank, coa, so)
     */
    public function supportData()
    {
        return response()->json([
            'status' => true,
            'data_bank' => AktKasbankin::getBank(),
            'data_coa' => AktKasbankin::getCoa(),
            'data_so' => AktKasbankin::getSo(),
        ]);
    }

    /**
     * Mendapatkan detail SO
     */
    public function getSoDetail($id_so)
    {
        $data = AktKasbankin::getSoDetail($id_so);
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Proses simpan data header dan detail
     */
    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $id_bank = $request->id_bank;
            $f_dp = $request->f_dp;
            $type_kb = $request->type_kb;
            $v_desc = $request->v_desc;
            
            $id_so = null;
            if ($f_dp == 'on' || $f_dp == true || $f_dp == 1) {
                $id_so = $request->id_so;
                $f_dp = true;
            } else {
                $f_dp = false;
            }
            
            $d_bank = date('Y-m-d', strtotime($request->d_bank));
            $periode = date('Ym', strtotime($d_bank));
            
            $v_amount = str_replace(',', '', $request->v_amount);
            
            $id_coa_array = $request->id_coa; 
            $amount_array = $request->amount; 
            $deskripsi_array = $request->deskripsi; 
            
            // Cek ketersediaan fungsi helper runningnumber_bulan2
            $code_kb_masuk = '';
            if (function_exists('runningnumber_bulan2')) {
                if ($type_kb == 'k') {
                    $code_kb_masuk = runningnumber_bulan2('KM', $periode);
                } else if ($type_kb == 'b') {
                    $code_kb_masuk = runningnumber_bulan2('BM', $periode);
                }
            } else {
                $prefix = strtoupper($type_kb) == 'K' ? 'KM' : 'BM';
                $code_kb_masuk = $prefix . '-' . $periode . '-' . rand(1000, 9999);
            }
            
            // Simpan ke tb_kb_masuk_hdr
            $id_kb_masuk = DB::table('tb_kb_masuk_hdr')->insertGetId([
                'code_kb_masuk' => $code_kb_masuk,
                'type_kb' => $type_kb,
                'id_bank' => $id_bank,
                'd_bank' => $d_bank,
                'v_amount' => $v_amount,
                'v_balance' => $v_amount,
                'f_dp' => $f_dp ? 1 : 0,
                'id_so' => $id_so,
                'deskripsi' => $v_desc,
                'date_create' => now()
            ]);
            
            // Simpan ke tb_kb_masuk_dtl 
            if ($id_coa_array && is_array($id_coa_array)) {
                foreach ($id_coa_array as $key => $id_coa_baru) {
                    $amount_baru = str_replace(',', '', $amount_array[$key] ?? 0);
                    $deskripsi_baru = $deskripsi_array[$key] ?? '';
                    
                    $coa = AktKasbankin::getCoaById($id_coa_baru);
                    $coa_name = $coa ? $coa->coa_name : '';
                    
                    DB::table('tb_kb_masuk_dtl')->insert([
                        'id_kb_masuk' => $id_kb_masuk,
                        'id_coa' => $id_coa_baru,
                        'v_amount' => $amount_baru,
                        'coa_name' => $coa_name,
                        'deskripsi' => $deskripsi_baru
                    ]);
                }
            }
            
            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Data berhasil disimpan',
                'kode' => $code_kb_masuk
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }
}
