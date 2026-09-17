<?php

namespace App\Models\listpayment;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Listpayment extends Model
{
    public static function data_payment($id_invoice)
    {
        return DB::select("select
        GROUP_CONCAT(CONCAT( 
            a.v_amount,
            '|',
            a.payment_ref,
            '|',
            a.date_draft,
            '|',
            case
                when a.date_cair is not null then a.date_cair
                else ''
            end,
            '|',
            a.status_payment,
            '|',
            b.nm_payment_method,
            '|',
            case
                when c.nm_rekening is not null then c.nm_rekening
                else ''
            end,
            '|',
            a.id_payment_method,
            '|',
            case
                when a.code_giro is not null then a.code_giro
                else ''
            end
            )) as 'detail_payment'
        from
            tb_invoice_dtl a
        inner join m_payment_method_hdr b on
            (a.id_payment_method = b.id_payment_method)
        left join m_bank c on
            (a.id_bank = c.id_bank)
        where a.id_invoice  = ?", [$id_invoice]);
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
}
