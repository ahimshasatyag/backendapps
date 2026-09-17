<?php

namespace App\Http\Controllers\listpayment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\listpayment\Listpayment;
use Illuminate\Support\Facades\DB;

class ListpaymentController extends Controller
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

        $where = '';
        $where_periode = "";
        $where_periode_ytd = "";

        if ($periode_input != '') {
            if ($ck_periode == "false" || $ck_periode == false) {
                $periode = date('Ym', strtotime('01-'.$periode_input));
                $periode_tahun = date('Y', strtotime('01-'.$periode_input));
                $where_periode = "DATE_FORMAT(b.date_so , '%Y%m') = '$periode' and";

                $where_periode_ytd = "DATE_FORMAT(b.date_so , '%Y%m') >= '$periode_tahun'
                and DATE_FORMAT(b.date_so , '%Y%m') <= '$periode' and";
            }
        }

        if ($id_customers != "ALL" && $id_customers != '' && $id_customers != null) {
            $where .= " and b.id_customers = '$id_customers'";
        }

        if ($id_product != "ALL" && $id_product != '' && $id_product != null) {
            $cari = [];
            $id_product_upper = strtoupper($id_product);

            foreach (explode(',', $id_product_upper) as $word) {
                $cari[] = "UPPER(d.code_product) like '%$word%'";
                $cari[] = "UPPER(d.nm_product) like '%$word%'";
            }
            $hasil_cari = implode(" OR ", $cari);
            $where .= " and (".$hasil_cari.")";
        }

        $queryStr = "
    select
    DATE_FORMAT(b.date_so,'%d/%m/%Y') as date_so,
    b.code_so,
    c.nm_customers,
    GROUP_CONCAT(a.product_price) as 'harga_ppn',
    GROUP_CONCAT(case when b.flag_cancel = 1 then 0 else a.nqty end ) as 'tot_qty',
    GROUP_CONCAT(d.code_product) as 'code_product',
    GROUP_CONCAT(a.id_product) as 'id_product',
    GROUP_CONCAT(d.nm_product) as 'nm_product',
    GROUP_CONCAT(e.nm_product_brand) as 'nm_product_brand',
    GROUP_CONCAT(k.type_kategori) as 'type_kategori',
    f.nm_karyawan,
    g.nm_type_pembayaran,
    b.ntenor,
    b.keterangan,
    b.vcurrency,
    b.flag_ppn,
    i.code_invoice ,
    DATE_FORMAT(i.date_invoice,'%d/%m/%Y') as date_invoice,
    a.id_so,
    b.id_customers,
    i.id_invoice,
    b.nppn_amount,
    b.nkurs,
    b.ndp_persen,
    b.vcurrency,
    b.ndp_amount,
    b.ntenor_amount,
    b.no_po_cust,
    b.success_fee,
    b.freight,
    b.teknisi,
    b.forklift,
    b.freight_amount,
    b.teknisi_amount,
    b.forklift_amount
from
    tb_so_dtl a
inner join tb_so_hdr b on
    (a.id_so = b.id_so)
inner join m_customers c on
    (b.id_customers = c.id_customers)
inner join m_product d on
    (a.id_product = d.id_product)
inner join m_product_brand e on
    (d.id_product_brand = e.id_product_brand)
inner join m_karyawan f on
    (b.id_karyawan = f.id_karyawan)
inner join m_type_bayar_hdr g on
    (b.id_type_pembayaran = g.id_type_pembayaran)
inner join m_type_bayar_dtl h on
    (b.id_cara_pembayaran = h.id_cara_pembayaran)
left join tb_invoice_hdr i on
    (b.id_so = i.id_so)
inner join m_product_sub_kategori k on
    (d.id_product_sub_kategori = k.id_product_sub_kategori)
where $where_periode
b.status_so in('SALES ORDER', 'SALE TO INVOICE')
$where
group by
    b.date_so, 	b.code_so,
    c.nm_customers, f.nm_karyawan,
    g.nm_type_pembayaran,
    b.ntenor,
    b.keterangan,
    b.vcurrency,
    b.flag_ppn,
    b.nppn_amount,
    i.code_invoice ,
    i.date_invoice,
    a.id_so,
    b.id_customers,
    i.id_invoice,
    b.nkurs,
    b.ndp_persen,
    b.vcurrency,
    b.ndp_amount,
    b.ntenor_amount,
    b.no_po_cust,
    b.success_fee,
    b.freight,
    b.teknisi,
    b.forklift,
    b.freight_amount,
    b.teknisi_amount,
    b.forklift_amount
order by b.date_so";

        $data = DB::select($queryStr);

        $queryLapStr = "select
    sum(case when b.vcurrency = 'USD' then (a.product_price * b.nkurs) * a.nqty else a.product_price * a.nqty end) + 
    COALESCE(sum(case when b.vcurrency = 'USD' then (opt.ntot_opt_price_netto * b.nkurs) else opt.ntot_opt_price_netto end), 0) as 'product_price',
    sum(a.nqty) + COALESCE(sum(opt.qty), 0) as 'nqty',
    k.type_kategori,
    'month' as 'kategori'
from
    tb_so_dtl a
    inner join tb_so_hdr b on (a.id_so = b.id_so)
    inner join m_customers c on (b.id_customers = c.id_customers)
    inner join m_product d on (a.id_product = d.id_product)
    inner join m_product_brand e on (d.id_product_brand = e.id_product_brand)
    inner join m_karyawan f on (b.id_karyawan = f.id_karyawan)
    inner join m_type_bayar_hdr g on (b.id_type_pembayaran = g.id_type_pembayaran)
    inner join m_type_bayar_dtl h on (b.id_cara_pembayaran = h.id_cara_pembayaran)
    inner join m_product_sub_kategori k on (d.id_product_sub_kategori = k.id_product_sub_kategori)
    left join tb_so_dtl_options opt on (a.id_so = opt.id_so and a.id_product = opt.id_product)
where
     $where_periode
    b.flag_cancel = 0
    and b.status_so in('SALES ORDER', 'SALE TO INVOICE')
    $where
group by
    k.type_kategori

union all

select
    sum(case when b.vcurrency = 'USD' then (a.product_price * b.nkurs) * a.nqty else a.product_price * a.nqty end) + 
    COALESCE(sum(case when b.vcurrency = 'USD' then (opt.ntot_opt_price_netto * b.nkurs) else opt.ntot_opt_price_netto end), 0) as 'product_price',
    sum(a.nqty) + COALESCE(sum(opt.qty), 0) as 'nqty',
    k.type_kategori,
    'ytd' as 'kategori'
from
    tb_so_dtl a
    inner join tb_so_hdr b on (a.id_so = b.id_so)
    inner join m_customers c on (b.id_customers = c.id_customers)
    inner join m_product d on (a.id_product = d.id_product)
    inner join m_product_brand e on (d.id_product_brand = e.id_product_brand)
    inner join m_karyawan f on (b.id_karyawan = f.id_karyawan)
    inner join m_type_bayar_hdr g on (b.id_type_pembayaran = g.id_type_pembayaran)
    inner join m_type_bayar_dtl h on (b.id_cara_pembayaran = h.id_cara_pembayaran)
    inner join m_product_sub_kategori k on (d.id_product_sub_kategori = k.id_product_sub_kategori)
    left join tb_so_dtl_options opt on (a.id_so = opt.id_so and a.id_product = opt.id_product)
where
    $where_periode_ytd
    b.flag_cancel = 0
    and b.status_so in('SALES ORDER', 'SALE TO INVOICE')
 $where
group by
    k.type_kategori";

        $data_lap = DB::select($queryLapStr);

        $hasil = [];
        $status = false;

        if (count($data) > 0) {
            $status = true;

            foreach ($data as $key => $value) {
                $hasil[$key] = (array) $value;
                $id_invoice = $hasil[$key]['id_invoice'];

                $hasil[$key]['detail_payment'] = null;

                if ($id_invoice) {
                    $data_payment = Listpayment::data_payment($id_invoice);
                    if (count($data_payment) > 0) {
                        $hasil[$key]['detail_payment'] = $data_payment[0]->detail_payment;
                    }
                }

                $id_so = $hasil[$key]['id_so'];

                $date_do_tmp = '';
                $date_delivery_tmp = '';

                $id_products = $hasil[$key]['id_product'];
                if ($id_products) {
                    $id_products_array = explode(',', $id_products);

                    foreach ($id_products_array as $value_id_product) {
                        $data_do = DB::select("select b.date_do, b.date_delivery from tb_do_dtl a
inner join tb_do_hdr b on(a.id_do = b.id_do)
where b.id_so = ?
and a.id_product = ?", [$id_so, $value_id_product]);

                        if (count($data_do) > 0) {
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
                    }
                }

                // delete last comma
                if ($date_do_tmp != '') $date_do_tmp = substr($date_do_tmp, 0, -1);
                if ($date_delivery_tmp != '') $date_delivery_tmp = substr($date_delivery_tmp, 0, -1);

                // option so
                $options_so = DB::select("select * from tb_so_dtl_options a where id_so = ?", [$id_so]);

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
        $data_header = Listpayment::data_header($id_so);
        $header = count($data_header) > 0 ? $data_header[0] : null;

        $data_invoice_dtl = [];
        if ($header) {
            $data_invoice_dtl = Listpayment::data_invoice_dtl($header->id_invoice);
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
        $data = Listpayment::data_header_so($id_so);
        $data_barang = Listpayment::data_barang_so($id_so);

        return response()->json([
            'status' => true,
            'id_so' => $id_so,
            'data' => count($data) > 0 ? $data[0] : null,
            'data_barang' => $data_barang,
        ]);
    }
}
