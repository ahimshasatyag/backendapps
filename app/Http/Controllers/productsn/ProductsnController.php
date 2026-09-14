<?php

namespace App\Http\Controllers\productsn;

use App\Http\Controllers\Controller;
use App\Models\productsn\ProductSn;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductsnController extends Controller
{
    public function index(Request $request)
    {
        $data = ProductSn::with(['product' => function ($query) {
            $query->select('id_product', 'code_product', 'nm_product');
        }])->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function supportData()
    {
        $data_barang = Product::select('id_product', 'code_product', 'nm_product')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'data_barang' => $data_barang
            ]
        ]);
    }

    public function show($id)
    {
        $data = ProductSn::with(['product' => function ($query) {
            $query->select('id_product', 'code_product', 'nm_product');
        }])->find($id);

        if (!$data) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product SN not found'
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
            'sn' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $cek_sn = ProductSn::where('sn', $request->sn)->first();

        if ($cek_sn) {
            return response()->json([
                'status' => 'error',
                'message' => 'Serial Number sudah ada.'
            ], 409);
        }

        DB::beginTransaction();

        try {
            $productsn = new ProductSn();
            $productsn->id_product = $request->id_product;
            $productsn->sn = $request->sn;
            $productsn->nqty = $request->nqty ?? 0;
            $productsn->save();

            DB::commit();

            Log::info('Simpan Data Product SN Kode : ' . $productsn->id_product_sn);

            return response()->json([
                'status' => 'success',
                'message' => 'Product SN created successfully',
                'data' => $productsn
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Simpan Product SN: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $productsn = ProductSn::find($id);

        if (!$productsn) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product SN not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'id_product' => 'required',
            'sn' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $cek_sn = ProductSn::where('sn', $request->sn)
            ->where('id_product_sn', '!=', $id)
            ->first();

        if ($cek_sn) {
            return response()->json([
                'status' => 'error',
                'message' => 'Serial Number sudah ada.'
            ], 409);
        }

        DB::beginTransaction();

        try {
            $productsn->id_product = $request->id_product;
            $productsn->sn = $request->sn;
            $productsn->nqty = $request->nqty ?? 0;
            $productsn->save();

            DB::commit();

            Log::info('Update Data Product SN Kode : ' . $productsn->id_product_sn);

            return response()->json([
                'status' => 'success',
                'message' => 'Product SN updated successfully',
                'data' => $productsn
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Update Product SN: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengubah data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $productsn = ProductSn::find($id);

        if (!$productsn) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product SN not found'
            ], 404);
        }

        DB::beginTransaction();

        try {
            $productsn->delete();

            DB::commit();

            Log::info('Hapus Data Product SN Kode : ' . $id);

            return response()->json([
                'status' => 'success',
                'message' => 'Product SN deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error Delete Product SN: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}
