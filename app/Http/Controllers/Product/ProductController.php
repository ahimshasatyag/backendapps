<?php

namespace App\Http\Controllers\Product;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use App\Models\Product\ProductBrand;
use App\Models\Product\ProductKategori;
use App\Models\Product\ProductSatuan;
use App\Models\Product\ProductSubKategori;
use App\Models\Product\ProductPriceOpt;
use App\Models\Product\ProductOpt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::with(['kategori', 'subKategori', 'satuan', 'brand'])->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    public function supportData()
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'data_kategori' => ProductKategori::all(),
                'data_brand' => ProductBrand::all(),
                'data_satuan' => ProductSatuan::all()
            ]
        ]);
    }

    public function getSubKategori(Request $request)
    {
        $id_product_kategori = $request->input('id_product_kategori');
        $data_sub_kategori = ProductSubKategori::where('id_product_kategori', $id_product_kategori)->get();

        return response()->json([
            'status' => 'success',
            'data' => $data_sub_kategori
        ]);
    }

    public function show($id)
    {
        $product = Product::with('options')->find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'product' => $product,
                'data_kategori' => ProductKategori::all(),
                'data_brand' => ProductBrand::all(),
                'data_satuan' => ProductSatuan::all(),
                'data_sub_kategori' => ProductSubKategori::where('id_product_kategori', $product->id_product_kategori)->get()
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code_product' => 'required|string|unique:m_product,code_product',
            'nm_product' => 'nullable|string',
            'id_product_kategori' => 'nullable|integer',
            'id_product_sub_kategori' => 'nullable|integer',
            'id_product_satuan' => 'nullable|string',
            'id_product_brand' => 'nullable|string',
            'product_deskripsi' => 'nullable|string',
            'product_refference' => 'nullable|string',
            'link_brosur' => 'nullable|mimes:pdf',
            'link_foto' => 'nullable|mimes:png,jpg,jpeg,bmp'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Handle File Uploads
            $link_brosur = null;
            $link_foto = null;

            if ($request->hasFile('link_brosur')) {
                $file = $request->file('link_brosur');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('assets/brosur'), $filename);
                $link_brosur = $filename;
            }

            if ($request->hasFile('link_foto')) {
                $file = $request->file('link_foto');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('assets/upload'), $filename);
                $link_foto = $filename;
            }

            // Handle Brand (Create if not exists)
            $id_product_brand = $request->id_product_brand;
            if ($id_product_brand) {
                $brand = ProductBrand::where('id_product_brand', strtoupper($id_product_brand))->first();
                if (!$brand) {
                    $brand = ProductBrand::create([
                        'id_product_brand' => strtoupper($id_product_brand),
                        'nm_product_brand' => $id_product_brand
                    ]);
                }
                $id_product_brand = $brand->id_product_brand;
            }

            $product = Product::create([
                'code_product' => $request->code_product,
                'nm_product' => $request->nm_product,
                'id_product_kategori' => $request->id_product_kategori,
                'id_product_sub_kategori' => $request->id_product_sub_kategori,
                'id_product_satuan' => $request->id_product_satuan,
                'id_product_brand' => $id_product_brand,
                'product_deskripsi' => $request->product_deskripsi,
                'product_refference' => $request->product_refference,
                'link_brosur' => $link_brosur,
                'link_foto' => $link_foto,
                'flag_active' => '1'
            ]);

            // Save options
            $jml = $request->input('jml', 0);
            for ($i = 1; $i <= $jml; $i++) {
                $nm_product_opt = $request->input("nm_product_opt" . $i);
                if ($nm_product_opt) {
                    ProductPriceOpt::create([
                        'id_product' => $product->id_product,
                        'nm_product_opt' => $nm_product_opt,
                        'amount' => 0,
                        'f_cancel' => '0'
                    ]);
                    
                    // Insert into master table m_product_opt if not exists
                    if (!ProductOpt::where('nm_product_opt', $nm_product_opt)->exists()) {
                        ProductOpt::create(['nm_product_opt' => $nm_product_opt]);
                    }
                }
            }

            if ($request->has('options') && is_array($request->options)) {
                foreach ($request->options as $opt) {
                    if (isset($opt['nm_product_opt'])) {
                        ProductPriceOpt::create([
                            'id_product' => $product->id_product,
                            'nm_product_opt' => $opt['nm_product_opt'],
                            'amount' => 0,
                            'f_cancel' => '0'
                        ]);
                        
                        // Insert into master table m_product_opt if not exists
                        if (!ProductOpt::where('nm_product_opt', $opt['nm_product_opt'])->exists()) {
                            ProductOpt::create(['nm_product_opt' => $opt['nm_product_opt']]);
                        }
                    }
                }
            }

            DB::commit();
            Log::info('Simpan Data Product Kode : ' . $product->id_product);

            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully',
                'data' => $product
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create Product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'code_product' => 'required|string|unique:m_product,code_product,' . $id . ',id_product',
            'nm_product' => 'nullable|string',
            'id_product_kategori' => 'nullable|integer',
            'id_product_sub_kategori' => 'nullable|integer',
            'id_product_satuan' => 'nullable|string',
            'id_product_brand' => 'nullable|string',
            'product_deskripsi' => 'nullable|string',
            'product_refference' => 'nullable|string',
            'link_brosur' => 'nullable|mimes:pdf',
            'link_foto' => 'nullable|mimes:png,jpg,jpeg,bmp'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $link_brosur = $product->link_brosur;
            $link_foto = $product->link_foto;

            if ($request->hasFile('link_brosur')) {
                if ($link_brosur && file_exists(public_path('assets/brosur/' . $link_brosur))) {
                    unlink(public_path('assets/brosur/' . $link_brosur));
                }
                $file = $request->file('link_brosur');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('assets/brosur'), $filename);
                $link_brosur = $filename;
            }

            if ($request->hasFile('link_foto')) {
                if ($link_foto && file_exists(public_path('assets/upload/' . $link_foto))) {
                    unlink(public_path('assets/upload/' . $link_foto));
                }
                $file = $request->file('link_foto');
                $filename = time() . '_' . $file->getClientOriginalName();
                $file->move(public_path('assets/upload'), $filename);
                $link_foto = $filename;
            }

            $id_product_brand = $request->id_product_brand;
            if ($id_product_brand) {
                $brand = ProductBrand::where('id_product_brand', strtoupper($id_product_brand))->first();
                if (!$brand) {
                    $brand = ProductBrand::create([
                        'id_product_brand' => strtoupper($id_product_brand),
                        'nm_product_brand' => $id_product_brand
                    ]);
                }
                $id_product_brand = $brand->id_product_brand;
            }

            $product->update([
                'code_product' => $request->code_product,
                'nm_product' => $request->nm_product,
                'id_product_kategori' => $request->id_product_kategori,
                'id_product_sub_kategori' => $request->id_product_sub_kategori,
                'id_product_satuan' => $request->id_product_satuan,
                'id_product_brand' => $id_product_brand,
                'product_deskripsi' => $request->product_deskripsi,
                'product_refference' => $request->product_refference,
                'link_brosur' => $link_brosur,
                'link_foto' => $link_foto,
                'flag_active' => $request->has('flag_active') ? $request->flag_active : $product->flag_active
            ]);

            ProductPriceOpt::where('id_product', $id)->update(['f_cancel' => '1']);

            $jml = $request->input('jml', 0);
            for ($i = 1; $i <= $jml; $i++) {
                $nm_product_opt = $request->input("nm_product_opt" . $i);
                if ($nm_product_opt) {
                    ProductPriceOpt::create([
                        'id_product' => $id,
                        'nm_product_opt' => $nm_product_opt,
                        'amount' => 0,
                        'f_cancel' => '0'
                    ]);
                    
                    if (!ProductOpt::where('nm_product_opt', $nm_product_opt)->exists()) {
                        ProductOpt::create(['nm_product_opt' => $nm_product_opt]);
                    }
                }
            }

            if ($request->has('options') && is_array($request->options)) {
                foreach ($request->options as $opt) {
                    if (isset($opt['nm_product_opt'])) {
                        ProductPriceOpt::create([
                            'id_product' => $id,
                            'nm_product_opt' => $opt['nm_product_opt'],
                            'amount' => 0,
                            'f_cancel' => '0'
                        ]);
                        
                        if (!ProductOpt::where('nm_product_opt', $opt['nm_product_opt'])->exists()) {
                            ProductOpt::create(['nm_product_opt' => $opt['nm_product_opt']]);
                        }
                    }
                }
            }

            DB::commit();
            Log::info('Update Data Product Kode : ' . $id);

            return response()->json([
                'status' => 'success',
                'message' => 'Product updated successfully',
                'data' => $product
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update Product: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'status' => 'error',
                'message' => 'Product not found'
            ], 404);
        }
        
        $product->delete();

        Log::info('Hapus Data Product Kode : ' . $id);

        return response()->json([
            'status' => 'success',
            'message' => 'Product deleted successfully'
        ]);
    }
}
