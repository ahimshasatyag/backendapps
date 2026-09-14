<?php

namespace App\Http\Controllers\productpriceagent;

use App\Http\Controllers\Controller;
use App\Models\productprice\ProductPrice;
use App\Models\productpricemkt\ProductPriceHistorySearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProductpriceagentController extends Controller
{
    public function index(Request $request)
    {
        $data = ProductPrice::with(['product' => function ($query) {
            $query->select('id_product', 'code_product', 'nm_product');
        }])
        ->where('flag_active', 1)
        ->orderBy('date_update', 'desc')
        ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function show(Request $request, $id)
    {
        $data = ProductPrice::with(['product' => function ($query) {
            $query->select('id_product', 'code_product', 'nm_product', 'link_brosur');
        }])
        ->where('id_product', $id)
        ->where('flag_active', 1)
        ->first();

        if ($data) {
            $username = $request->user()->username ?? 'system';
            
            // Insert history search
            ProductPriceHistorySearch::create([
                'id_product' => $id,
                'username' => $username,
                'date_create' => now(),
            ]);

            Log::info('Mencari Product ' . $id);

            $kurs = $data->kurs_bank ?? 15000;

            $hasil = [
                'id_product' => $data->id_product,
                'code_product' => $data->product->code_product ?? '',
                'nm_product' => $data->product->nm_product ?? '',
                'product_price' => $data->product_price_agent, // Mapping product_price_agent to product_price based on Cform.php
                'product_price_agent' => $data->product_price_agent,
                'date_create' => $data->date_update,
                'kurs_bank' => $kurs,
                'estimasi' => $kurs * $data->product_price_agent,
                'id_product_global' => encrypt($data->id_product),
                'link_brosur' => $data->product->link_brosur ?? '',
            ];

            return response()->json([
                'status' => 'success',
                'data' => $hasil
            ]);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found'
            ], 404);
        }
    }

    public function tambahKeranjang(Request $request)
    {
        $id_product = $request->input('id_product');
        $qty = $request->input('qty', 1);

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil ditambahkan ke keranjang',
            'data' => [
                'id_product' => $id_product,
                'qty' => $qty
            ]
        ]);
    }
}
