<?php

namespace App\Http\Controllers\productpricereq;

use App\Http\Controllers\Controller;
use App\Models\productpricereq\ProductPriceReq;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProductpricereqController extends Controller
{
    public function index(Request $request)
    {
        $data = ProductPriceReq::select('m_product_price_req.*', 'm_product.code_product', 'm_product.nm_product', 'm_users.nm_users')
            ->join('m_product', 'm_product_price_req.id_product', '=', 'm_product.id_product')
            ->leftJoin('m_users', 'm_product_price_req.username', '=', 'm_users.username')
            ->orderBy('m_product_price_req.id', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function supportData()
    {
        $data_product = Product::select('id_product', 'code_product', 'nm_product')
            ->where('flag_active', 1)
            ->get();
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'data_product' => $data_product
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_product' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $req = new ProductPriceReq();
            $req->id_product = $request->id_product;
            $req->product_price_req = 0;
            $req->product_price_acc = 0;
            $req->username = $request->username ?? ($request->user()->username ?? 'system');
            $req->status = 'DRAFT';
            $req->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Berhasil menambahkan data',
                'data' => $req
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Simpan Product Price Req: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $data = ProductPriceReq::select('m_product_price_req.*', 'm_product.code_product', 'm_product.nm_product', 'm_users.nm_users')
            ->join('m_product', 'm_product_price_req.id_product', '=', 'm_product.id_product')
            ->leftJoin('m_users', 'm_product_price_req.username', '=', 'm_users.username')
            ->where('m_product_price_req.id', $id)
            ->first();

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function update(Request $request, $id)
    {
        $req = ProductPriceReq::find($id);
        if (!$req) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $f_acc = $request->input('f_acc', 0);

        DB::beginTransaction();
        try {
            if ($f_acc == 0) {
                $req->id_product = $request->id_product;
                $req->save();
            } else {
                $req->product_price_acc = str_replace(',', '', $request->product_price_acc);
                $req->status = 'SUCCESS';
                $req->save();

                // Send Whatsapp Notification
                $product = Product::find($req->id_product);
                $pesan = "Permintaan Update Harga Anda\n"
                       . "Kode Barang: " . ($product->code_product ?? '') . "\n"
                       . "Nama Barang: " . ($product->nm_product ?? '') . "\n"
                       . "Harga Request: USD " . number_format($req->product_price_req) . "\n"
                       . "Harga Acc: USD " . number_format($req->product_price_acc) . "\n"
                       . "Status: SUCCESS\n";

                DB::table('m_whatsapp_message_pending')->insert([
                    'data' => json_encode(['message' => $pesan, 'number' => '6282226076210']),
                    'date' => now(),
                    'status' => 'Pending'
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Update berhasil',
                'data' => $req
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Update Product Price Req: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $req = ProductPriceReq::find($id);
        if (!$req) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $status = strtoupper($request->status);

        DB::beginTransaction();
        try {
            if ($status == 'CONFIRM') {
                $product = Product::find($req->id_product);
                
                $user = DB::table('m_users')->where('username', $req->username)->first();
                $nm_users = $user->nm_users ?? $req->username;

                $pesan = "*Permintaan Update Harga*\n"
                       . "Kode Barang: " . ($product->code_product ?? '') . "\n"
                       . "Nama Barang: " . ($product->nm_product ?? '') . "\n"
                       . "Request: " . $nm_users . "\n\n"
                       . "*Silahkan buka EMMA web untuk update harga";

                DB::table('m_whatsapp_message_pending')->insert([
                    'data' => json_encode(['message' => $pesan, 'number' => '6282226076210']),
                    'date' => now(),
                    'status' => 'Pending'
                ]);

                $req->f_kirim = 9;
            }

            $req->status = $status;
            $req->save();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Status berhasil diubah',
                'data' => $req
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Update Status Product Price Req: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah status: ' . $e->getMessage()
            ], 500);
        }
    }
}
