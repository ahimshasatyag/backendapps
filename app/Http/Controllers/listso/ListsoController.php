<?php

namespace App\Http\Controllers\listso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\listso\Listso;
use Illuminate\Support\Facades\DB;

class ListsoController extends Controller
{
    /**
     * Data AR Report
     */
    public function dataArReport(Request $request)
    {
        $periode_input = $request->input('periode');
        $id_product = $request->input('id_product');
        $id_customers = $request->input('id_customers');
        $ck_periode = $request->input('ck_periode');

        $result = Listso::getDataArReport('', $id_product, $id_customers, $ck_periode, $periode_input);
        
        $data = $result['data'];
        $data_lap = $result['data_lap'];

        $hasil = [];
        $status = false;

        if (count($data) > 0) {
            $status = true;

            foreach ($data as $key => $value) {
                $hasil[$key] = (array) $value;
                $code_so = $hasil[$key]['code_so'];

                $data_so = DB::table('tb_so_hdr')->where('code_so', $code_so)->first();
                $id_so = $data_so->id_so;

                $data_do = DB::table('tb_do_hdr')->where('id_so', $id_so)->get();

                $date_do_tmp = '';
                $date_delivery_tmp = '';

                if ($data_do->count() > 0) {
                    foreach ($data_do as $value_do) {
                        $value_do = (array) $value_do;
                        
                        $date_do_db_tmp = $value_do['date_do'];
                        if ($date_do_db_tmp != '' && $date_do_db_tmp != null) {
                            $date_do_db_tmp = date('d/m/Y', strtotime($date_do_db_tmp));
                        }

                        $date_delivery_db_tmp = $value_do['date_delivery'];
                        if ($date_delivery_db_tmp != '' && $date_delivery_db_tmp != null) {
                            $date_delivery_db_tmp = date('d/m/Y', strtotime($date_delivery_db_tmp));
                        }

                        $date_do_tmp .= $date_do_db_tmp . ',';
                        $date_delivery_tmp .= $date_delivery_db_tmp . ',';
                    }
                }

                // delete last comma
                if ($date_do_tmp != '') $date_do_tmp = substr($date_do_tmp, 0, -1);
                if ($date_delivery_tmp != '') $date_delivery_tmp = substr($date_delivery_tmp, 0, -1);

                // option so
                $options_so = DB::table('tb_so_dtl_options')->where('id_so', $id_so)->get();

                $hasil[$key]['date_do'] = $date_do_tmp;
                $hasil[$key]['date_delivery'] = $date_delivery_tmp;
                $hasil[$key]['options_so'] = $options_so;
            }
        }

        return response()->json([
            'status' => $status,
            'data' => $hasil,
            'data_lap' => $data_lap
        ]);
    }

    /**
     * Detail SO
     */
    public function detailSo($id_so)
    {
        $data_header = Listso::data_header($id_so);
        $header = count($data_header) > 0 ? $data_header[0] : null;

        $data_invoice_dtl = [];
        if ($header) {
            $data_invoice_dtl = Listso::data_invoice_dtl($header->id_invoice);
        }

        return response()->json([
            'status' => true,
            'id_so' => $id_so,
            'data_header' => $header,
            'data_invoice_dtl' => $data_invoice_dtl,
        ]);
    }

    /**
     * View SO
     */
    public function viewSo($id_so)
    {
        $data = Listso::data_header_so($id_so);
        $data_barang = Listso::data_barang_so($id_so);

        return response()->json([
            'status' => true,
            'id_so' => $id_so,
            'data' => count($data) > 0 ? $data[0] : null,
            'data_barang' => $data_barang,
        ]);
    }
}
