<?php

namespace App\Models\listso;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Listso extends Model
{
    public static function data_invoice_dtl($id_invoice)
    {
        return DB::select("select
                        a.*,
                b.nm_payment_method
                from
                tb_invoice_dtl a,
                m_payment_method_hdr b
                where
                a.id_payment_method = b.id_payment_method
                and a.id_invoice = ?
                and a.f_cancel = '0'", [$id_invoice]);
    }

    public static function data_header($id_so)
    {
        return DB::select("select a.*, b.nm_customers , b.customers_address, b.customers_phone, c.code_so, c.nkurs, c.ndp_persen, c.ndp_amount, c.date_so, c.nppn_amount
        from tb_invoice_hdr a, m_customers b, tb_so_hdr c
        where a.id_customers = b.id_customers
        and a.id_so = c.id_so
        and a.id_customers = c.id_customers
        and a.id_so = ?", [$id_so]);
    }

    public static function data_product()
    {
        return DB::select("select * from m_product");
    }

    public static function data_header_so($id_so)
    {
        return DB::select("select a.code_so, a.date_so , a.date_estimasi, c.nm_karyawan, d.nm_customers ,
        d.customers_address , d.customers_email , d.customers_phone, b.nm_users,
        e.nm_type_pembayaran , f.nm_cara_pembayaran, a.flag_ppn ,  g.nm_waktu_bayar, a.vcurrency, a.delivery_term, a.keterangan, a.status_so,
        a.flag_ppn, a.nkurs, a.ndp_persen, a.ndp_amount, a.ntenor, a.ntenor_amount
        from tb_so_hdr a, m_users b, m_karyawan c , m_customers d, m_type_bayar_hdr e,
        m_type_bayar_dtl f, m_waktu_bayar g
        where a.username_create = b.username
        and a.id_karyawan = c.id_karyawan
        and a.id_customers = d.id_customers
        and a.id_type_pembayaran = e.id_type_pembayaran
        and a.id_cara_pembayaran = f.id_cara_pembayaran
        and a.id_waktu_bayar = g.id_waktu_bayar
        and a.id_so = ?", [$id_so]);
    }

    public static function data_barang_so($id_so)
    {
        return DB::select("select a.id_product, b.code_product, b.nm_product, b.product_deskripsi, a.status_barang, a.indent_amount,
        a.product_price, a.nqty , a.ntot_product_price_netto, c.nm_product_satuan, d.delivery_term
        from tb_so_dtl a, m_product b, m_product_satuan c, m_product_price d
        where
        a.id_product = b.id_product
        and b.id_product_satuan = c.id_product_satuan
        and a.id_product = d.id_product
        and b.id_product = d.id_product
        and a.id_so = ?", [$id_so]);
    }

    public static function getDataArReport($periode, $id_product, $id_customers, $ck_periode, $periode_input)
    {
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

        $queryStr = "select
        DATE_FORMAT(b.date_so,'%d/%m/%Y') as date_so,
        b.code_so,
        c.nm_customers,
        GROUP_CONCAT(a.product_price) as 'harga_ppn',
        GROUP_CONCAT(case when b.flag_cancel = 1 then 0 else a.nqty end ) as 'tot_qty',
        GROUP_CONCAT(d.code_product) as 'code_product',
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
        b.no_po_cust
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
        b.date_so,
        b.code_so,
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
        b.no_po_cust
    order by b.date_so";

        $data = DB::select($queryStr);

        $queryLapStr = "select
        sum(case when b.vcurrency = 'USD' then (a.product_price * b.nkurs) * a.nqty else a.product_price * a.nqty end ) as 'product_price',
        sum(a.nqty) as 'nqty',
        k.type_kategori,
        'month' as 'kategori'
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
    inner join m_product_sub_kategori k on
        (d.id_product_sub_kategori = k.id_product_sub_kategori)
    where
        $where_periode
        b.flag_cancel = 0
        and b.status_so in('SALES ORDER', 'SALE TO INVOICE')
        $where
    group by
        k.type_kategori
    union ALL
    select
        sum(case when b.vcurrency = 'USD' then (a.product_price * b.nkurs) * a.nqty else a.product_price * a.nqty end ) as 'product_price',
        sum(a.nqty) as 'nqty',
        k.type_kategori,
        'ytd' as 'kategori'
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
    inner join m_product_sub_kategori k on
        (d.id_product_sub_kategori = k.id_product_sub_kategori)
    where
        $where_periode_ytd
         b.flag_cancel = 0
        and b.status_so in('SALES ORDER', 'SALE TO INVOICE')
        $where
    group by
        k.type_kategori";

        $data_lap = DB::select($queryLapStr);

        return [
            'data' => $data,
            'data_lap' => $data_lap
        ];
    }
}
