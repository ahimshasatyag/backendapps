<?php

namespace App\Http\Controllers\productpricelog;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductpricelogController extends Controller
{
    public function index(Request $request)
    {

        $data = DB::table('m_product_price_history_search as a')
            ->join('m_users as b', 'a.username', '=', 'b.username')
            ->join('m_product as c', 'a.id_product', '=', 'c.id_product')
            ->select('a.username', 'b.nm_users', 'c.code_product', 'c.nm_product', DB::raw('count(a.id_product) as jml'))
            ->groupBy('a.username', 'b.nm_users', 'c.code_product', 'c.nm_product')
            ->orderBy('jml', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }
}
