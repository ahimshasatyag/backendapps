<?php

namespace App\Http\Controllers\productprice;

use App\Http\Controllers\Controller;
use App\Models\productprice\ProductPrice;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductpriceController extends Controller
{
    public function index(Request $request)
    {
        $data = ProductPrice::with(['product' => function ($query) {
            $query->select('id_product', 'code_product', 'nm_product', 'id_product_brand', 'product_deskripsi')->with('brand');
        }, 'history', 'options'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function supportData()
    {
        // Get products that are not in m_product_price
        $existingProductIds = ProductPrice::pluck('id_product')->toArray();
        $data_product = Product::select('id_product', 'code_product', 'nm_product')
            ->where('flag_active', 1)
            ->whereNotIn('id_product', $existingProductIds)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'data_product' => $data_product,
            ]
        ]);
    }

    public function show($id)
    {
        $data = ProductPrice::with(['product' => function ($query) {
            $query->select('id_product', 'code_product', 'nm_product', 'id_product_brand', 'product_deskripsi')->with('brand');
        }, 'history', 'options'])->find($id);

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Price not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id_product' => 'required',
            'product_price' => 'required',
            'product_price_agent' => 'required',
            'kurs_bank' => 'required',
            'delivery_term' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $cek_exist = ProductPrice::where('id_product', $request->id_product)->first();

        if ($cek_exist) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Price sudah ada.'
            ], 409);
        }

        DB::beginTransaction();

        try {
            $productprice = new ProductPrice();
            $productprice->id_product = $request->id_product;
            $productprice->product_price = str_replace(',', '', $request->product_price);
            $productprice->product_price_agent = str_replace(',', '', $request->product_price_agent);
            $productprice->kurs_bank = str_replace(',', '', $request->kurs_bank);
            $productprice->delivery_term = $request->delivery_term;
            $productprice->username = $request->user()->username ?? 'system';
            $productprice->flag_active = 1;
            $productprice->save();

            DB::commit();

            Log::info('Simpan Data Product Price Kode : ' . $productprice->id_product);

            return response()->json([
                'status' => 'success',
                'message' => 'Product Price created successfully',
                'data' => $productprice
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Simpan Product Price: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $productprice = ProductPrice::find($id);

        if (!$productprice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Price not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'product_price' => 'required',
            'product_price_agent' => 'required',
            'kurs_bank' => 'required',
            'delivery_term' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $productprice->product_price = str_replace(',', '', $request->product_price);
            $productprice->product_price_agent = str_replace(',', '', $request->product_price_agent);
            $productprice->kurs_bank = str_replace(',', '', $request->kurs_bank);
            $productprice->delivery_term = $request->delivery_term;
            $productprice->username = $request->user()->username ?? 'system';
            if ($request->has('flag_active')) {
                $productprice->flag_active = $request->flag_active;
            }
            $productprice->save();

            DB::commit();

            Log::info('Update Data Product Price Kode : ' . $productprice->id_product);

            return response()->json([
                'status' => 'success',
                'message' => 'Product Price updated successfully',
                'data' => $productprice
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Update Product Price: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $productprice = ProductPrice::find($id);

        if (!$productprice) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product Price not found'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $productprice->delete();

            DB::commit();

            Log::info('Hapus Data Product Price Kode : ' . $id);

            return response()->json([
                'status' => 'success',
                'message' => 'Product Price deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Delete Product Price: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}
