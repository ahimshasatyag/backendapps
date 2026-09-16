<?php

namespace App\Http\Controllers\cekserialnumber;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\cekserialnumber\Cekserialnumber;

class CekserialnumberController extends Controller
{
    /**
     * Menampilkan data 
     */
    public function index(Request $request)
    {
        $data = Cekserialnumber::getData();
        return response()->json([
            'status' => true,
            'data' => $data
        ]);
    }

    /**
     * Menampilkan detail serial 
     */
    public function detailSerial(Request $request)
    {
        $barcode = $request->input('barcode');
        
        if (!$barcode) {
            return response()->json([
                'status' => false,
                'message' => 'Barcode tidak ditemukan'
            ], 400);
        }

        $query = Cekserialnumber::getDetailSerialNumber($barcode);
        $history = Cekserialnumber::getHistoryServices($barcode);

        if ($query->count() > 0) {
            $data = [];
            foreach ($query as $row) {
                $data[] = [
                    "code_product" => $row->code_product ?: "-",
                    "nm_product" => $row->nm_product ?: "-",
                    "product_deskripsi" => $row->product_deskripsi ?: "-",
                    "customer" => $row->nm_customers ?: "-",
                    "customer_address" => $row->customers_address ?: "-",
                    "provinsi" => $row->provinsi ?: "-",
                    "kabupaten" => $row->kabupaten ?: "-",
                    "customer_phone" => $row->customers_phone ?: "-",
                    "customer_mobile" => $row->customers_mobile ?: "-",
                    "do_code" => $row->do_code ?: "-",
                    "waranty_start" => $row->waranty_start ? date('d-M-Y', strtotime($row->waranty_start)) : "-",
                    "waranty_time" => $row->waranty_time ?: "0",
                    "waranty_end" => $row->waranty_end ? date('d-M-Y', strtotime($row->waranty_end)) : "-",
                    "waranty_end_raw" => $row->waranty_end ?: null
                ];
            }

            $data_history = [];
            if ($history->isNotEmpty()) {
                foreach ($history as $h) {
                    if ($h->status == 'Cancel') {
                        continue;
                    }
                    $data_history[] = [
                        "cst_code" => $h->cst_code ?: "-",
                        "cst_date" => $h->cst_date ? date('d-M-Y', strtotime($h->cst_date)) : "-",
                        "catatan_kerusakan" => $h->catatan_kerusakan ?: "-",
                        "total_realisasi" => $h->total_realisasi ?: "0",
                        "laporan_akhir" => $h->laporan_akhir ?: "-",
                        "teknisi" => $h->teknisi ?: "-",
                    ];
                }
            }

            return response()->json([
                "status" => true,
                "data" => $data,
                "history" => $data_history,
            ]);
        } else {
            return response()->json([
                "status" => false,
                "message" => "Data tidak ditemukan"
            ]);
        }
    }
}
