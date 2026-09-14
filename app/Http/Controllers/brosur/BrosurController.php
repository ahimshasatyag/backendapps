<?php

namespace App\Http\Controllers\brosur;

use App\Http\Controllers\Controller;
use App\Models\Product\Product;
use Illuminate\Http\Request;
use setasign\Fpdi\Fpdi;
use Illuminate\Support\Facades\Log;

class BrosurController extends Controller
{
    public function index(Request $request)
    {
        // Mendapatkan produk yang memiliki link_brosur
        $data = Product::whereNotNull('link_brosur')
            ->where('link_brosur', '!=', '')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $data
        ]);
    }

    public function generate(Request $request)
    {
        // Parameter bisa dari POST / GET, berbentuk array ID atau string '1-2-3'
        $id_products = $request->input('products');
        $f_cover = $request->input('cover', '0');

        if (!$id_products) {
            return response()->json([
                'status' => 'error',
                'message' => 'No products selected'
            ], 400);
        }

        if (!is_array($id_products)) {
            $id_products = explode('-', $id_products);
        }

        try {
            $pdf = new Fpdi();
            $pdf->SetAuthor('Eka Maju Mesinindo');
            $pdf->SetTitle('Brosur Eka Maju Mesinindo');

            $data_barang = [];
            foreach ($id_products as $id_product) {
                $product = Product::find($id_product);
                if ($product && $product->link_brosur) {
                    $data_barang[] = $product;
                }
            }

            if (count($data_barang) == 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Produk tidak valid atau brosur tidak ditemukan'
                ], 404);
            }

            $no_page = 0;

            if ($f_cover == '1') {
                $coverPath = public_path('assets/brosur/cover.pdf');
                // Alternatif path jika menggunakan struktur CI lama di folder root:
                if (!file_exists($coverPath)) {
                    $coverPath = base_path('assets/brosur/cover.pdf'); 
                }

                if (file_exists($coverPath)) {
                    $pageCount = $pdf->setSourceFile($coverPath);
        
                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $pdf->importPage($pageNo);
                        $size = $pdf->getTemplateSize($templateId);
                        $orientation = $size['orientation'] ?? 'P';
                        $format = array($size['width'], $size['height']);
                        $pdf->AddPage($orientation, $format);
                        $pdf->useTemplate($templateId);

                        $no_page += 1;
                    }
                }
            }

            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 16);
            $pdf->Cell(0, 10, 'Daftar Isi', 0, 1, 'C');
            $pdf->Ln(10);
            $pdf->SetFont('Arial', '', 12);
            $pdf->SetLeftMargin(20);
            $pdf->SetRightMargin(20);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetLineWidth(0.1);
            $pdf->SetFont('', 'B');
            
            // Header 3 kolom
            $pdf->Cell(10, 7, 'No', 1, 0, 'C', true);
            $pdf->Cell(100, 7, 'Nama Barang', 1, 0, 'C', true);
            $pdf->Cell(50, 7, 'Halaman', 1, 1, 'C', true);

            $pdf->SetFont('', '');
            $fill = false;
            $no = 1;
            
            $no_page += 1;

            foreach ($data_barang as $barang) {
                $pdf->Cell(10, 7, $no, 1, 0, 'C', $fill);
                
                $link_brosur = public_path('assets/brosur/' . $barang->link_brosur);
                if (!file_exists($link_brosur)) {
                    $link_brosur = base_path('assets/brosur/' . $barang->link_brosur);
                }

                if (!file_exists($link_brosur)) {
                    $pdf->Cell(100, 7, $barang->code_product . ' - ' . $barang->nm_product, 1, 0, 'L', $fill);
                    $pdf->Cell(50, 7, 'Tidak Ditemukan', 1, 1, 'C', $fill);
                    $fill = !$fill;
                    $no++;
                    continue;
                }

                $pageCount = $pdf->setSourceFile($link_brosur);

                if ($no_page == 1) {
                    $no_page = 2;
                } else if ($no_page == 2) {
                    $no_page = 3;
                }
                
                $link1 = $pdf->AddLink();
                $pdf->SetLink($link1, 0, $no_page);

                $pdf->Cell(100, 7, $barang->code_product . ' - ' . $barang->nm_product, 1, 0, 'L', $fill, $link1);
                $pdf->Cell(50, 7, $no_page . ' - ' . ($no_page + $pageCount - 1), 1, 1, 'C', $fill, $link1);

                $fill = !$fill;
                $no++;
                $no_page += $pageCount;
            }

            foreach ($data_barang as $barang) {
                $link_brosur = public_path('assets/brosur/' . $barang->link_brosur);
                if (!file_exists($link_brosur)) {
                    $link_brosur = base_path('assets/brosur/' . $barang->link_brosur);
                }
                
                if (!file_exists($link_brosur)) continue;

                $pageCount = $pdf->setSourceFile($link_brosur);
    
                for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                    $templateId = $pdf->importPage($pageNo);
                    $size = $pdf->getTemplateSize($templateId);
                    $orientation = $size['orientation'] ?? 'P';
                    $format = array($size['width'], $size['height']);
                    $pdf->AddPage($orientation, $format);
                    $pdf->useTemplate($templateId);
                }
            }

            // Output PDF sebagai response download
            $pdfOutput = $pdf->Output('S');

            return response($pdfOutput)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="Brosur Eka Maju Mesinindo.pdf"');

        } catch (\Exception $e) {
            Log::error('Error generating PDF Brosur: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal generate PDF: ' . $e->getMessage()
            ], 500);
        }
    }
}
