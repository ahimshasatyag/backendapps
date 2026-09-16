<?php

namespace App\Http\Controllers\matauang;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\matauang\Matauang;

class MatauangController extends Controller
{
    /**
     * Menampilkan data semua kurs terbaru
     */
    public function index()
    {
        $kurs = Matauang::kurs();
        
        return response()->json([
            'status' => true,
            'data' => $kurs
        ]);
    }

    /**
     * Mengambil data kurs dan perbandingannya dengan mata uang tertentu (e.g., USD)
     */
    public function getKurs(Request $request)
    {
        // Mendapatkan base currency, misalnya dari POST data
        $mata_uang = $request->mata_uang;
        
        if (!$mata_uang) {
            return response()->json([
                'status' => false,
                'message' => 'Parameter mata_uang tidak ditemukan.'
            ], 400);
        }

        $base_kurs_data = Matauang::getKurs($mata_uang);
        
        if (empty($base_kurs_data)) {
            return response()->json([
                'status' => false,
                'message' => 'Mata uang base tidak ditemukan.'
            ], 404);
        }

        $base_kurs_usd = $base_kurs_data[0]->kurs;
        $all_kurs = Matauang::kurs();
        
        $result = [];
        foreach ($all_kurs as $row) {
            $result[] = [
                'mata_uang' => $row->mata_uang,
                'kurs' => $row->kurs,
                'kurs_dibandingkan' => $base_kurs_usd > 0 ? ($row->kurs / $base_kurs_usd) : 0,
                'date_create' => date('d-m-Y H:i:s', strtotime($row->date_create))
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $result
        ]);
    }
}
