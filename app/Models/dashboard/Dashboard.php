<?php

namespace App\Models\dashboard;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Dashboard extends Model
{
    public static function getCustomers()
    {
        return DB::select("SELECT * FROM m_customers");
    }

    public static function getArReport($dfrom, $dto, $id_customers, $all_data_ar)
    {
        $where = "";

        if ($id_customers != "ALL" && $id_customers != '') {
            $where .= " and a.id_customers = '$id_customers'";
        }

        if ($all_data_ar == "false" || $all_data_ar == false) {
            $where .= " and a.ntot_balance != 0";
        }

        return DB::select("select DATE_FORMAT(a.date_invoice, '%d-%m-%Y') as date_invoice, b.code_so, c.nm_customers, a.ntot_price_netto_amount, (a.ntot_price_netto_amount  - a.ntot_balance) as payment, a.ntot_balance
            from tb_invoice_hdr a, tb_so_hdr b, m_customers c
            where a.id_so = b.id_so
            and a.id_customers = b.id_customers
            and a.id_customers = c.id_customers
            and b.id_customers = c.id_customers
            and a.date_invoice >= ?
            and a.date_invoice <= ?
            $where", [$dfrom, $dto]);
    }

    public static function getTeknisiPP()
    {
        return DB::select("SELECT DISTINCT nm_karyawan FROM m_karyawan WHERE id_karyawan NOT IN
            (SELECT a.actual_id_karyawan
            FROM tb_afs_realisasi_teknisi a
            JOIN tb_afs_realisasi b ON a.lkt_sub_code = b.lkt_sub_code
            WHERE b.actual_starting_date = DATE(NOW())
            AND b.f_cancel = 0)
            AND id_karyawan_posisi = 4
            AND m_karyawan.flag_status != 0");
    }

    public static function getTeknisiPL()
    {
        return DB::select("SELECT DISTINCT nm_karyawan FROM m_karyawan WHERE id_karyawan NOT IN
            (SELECT a.actual_id_karyawan
            FROM tb_afs_realisasi_teknisi a
            JOIN tb_afs_realisasi b ON a.lkt_sub_code = b.lkt_sub_code
            WHERE b.actual_starting_date = DATE(NOW())
            AND b.f_cancel = 0)
            AND id_karyawan_posisi = 7
            AND m_karyawan.flag_status != 0");
    }

    public static function getJadwalLkt()
    {
        return DB::select("SELECT b.lkt_code, a.cst_code, c.nm_customers, d.lokasi, e.code_product,
            COALESCE(f.actual_description, b.description) AS actual_description, g.nm_karyawan
            FROM tb_afs_cst a
            JOIN tb_afs_lkt b ON a.id_afs_cst = b.id_afs_cst
            JOIN tb_afs_csr d ON a.id_afs_csr = d.id_afs_csr
            JOIN m_customers c ON d.id_customers = c.id_customers
            JOIN m_product e ON d.id_product = e.id_product
            JOIN m_karyawan g ON d.id_karyawan = g.id_karyawan
            LEFT JOIN tb_afs_realisasi f ON b.id_afs_lkt = f.id_afs_lkt AND f.f_cancel = 0
            WHERE b.starting_date = DATE(NOW())
            AND b.f_cancel = 0");
    }

    public static function getTotalRequestPendingProgres()
    {
        return DB::select("select sum(total_request) as total_request, sum(total_pending) as total_pending, sum(total_progres) as total_progres from(
            SELECT COUNT(a.id_afs_csr) AS total_request, 0 as total_pending, 0 as total_progres
            FROM tb_afs_csr a
            JOIN m_karyawan b ON a.id_karyawan = b.id_karyawan
            JOIN m_customers c ON a.id_customers = c.id_customers
            JOIN m_product d ON a.id_product = d.id_product
            WHERE a.csr_status = 'DRAFT' and a.f_cancel = 0
            union ALL
            select 0 as total_request, count(a.id_afs_cst) as total_pending, 0 as total_progres
            from tb_afs_cst a
            join tb_afs_csr b on a.id_afs_csr = b.id_afs_csr
            join m_karyawan c on b.id_karyawan = c.id_karyawan
            join m_customers d on b.id_customers = d.id_customers
            join m_product e on b.id_product = e.id_product
            where a.status  = 'OUTSTANDING'
            and b.f_cancel = 0
            union ALL
            select 0 as total_request, 0 as total_pending, count(a.id_afs_cst) as total_progres
            from tb_afs_cst a
            join tb_afs_csr b on a.id_afs_csr = b.id_afs_csr
            join m_karyawan c on b.id_karyawan = c.id_karyawan
            join m_customers d on b.id_customers = d.id_customers
            join m_product e on b.id_product = e.id_product
            where a.status  = 'ON PROGRESS' and b.f_cancel = 0
            ) x");
    }

    public static function getAgingAr()
    {
        return DB::select("select
        b.nm_customers,
        a.date_payment,
        a.v_amount,
        c.vcurrency
        from
        tb_payment_schdl a,
        m_customers b,
        tb_invoice_hdr c
        WHERE
        a.id_customers = b.id_customers
        and c.id_customers = b.id_customers
        and a.id_invoice = c.id_invoice
        and a.status_payment = 'TERIMA'
        and a.date_payment <= now()
        order by b.nm_customers asc, c.vcurrency asc");
    }

    public static function getArReport2($tanggal, $id_customers, $mata_uang)
    {
        $where = "";
        if ($id_customers != "ALL" && $id_customers != "") {
            $where .= " and z.id_customers = '$id_customers'";
        }

        return DB::select("select z.* from(
            select x.code_invoice, x.date_invoice, x.vcurrency,  x.id_customers, x.nm_customers, sum(x.total) as total, sum(x.sisa) as sisa from(
            select a.code_invoice, a.date_invoice, a.vcurrency,  a.id_customers, b.nm_customers, a.ntot_price_netto_amount as total, a.ntot_price_netto_amount as sisa  from tb_invoice_hdr a, m_customers b
            where a.id_customers = b.id_customers
            and a.date_invoice <= '$tanggal'
            and a.flag_cancel = '0'
            union all
            select b.code_invoice, b.date_invoice, b.vcurrency,  b.id_customers, c.nm_customers, 0 as total , (a.v_amount * -1) as sisa  from tb_invoice_dtl a, tb_invoice_hdr b, m_customers c
            where a.id_invoice  = b.id_invoice
            and b.id_customers = c.id_customers
            and a.status_payment = 'CAIR'
            and b.flag_cancel = '0'
            and a.date_cair <= '$tanggal'
            and b.date_invoice <= '$tanggal'
            ) as x
            where x.vcurrency = '$mata_uang'
            group by x.code_invoice, x.date_invoice, x.vcurrency, x.id_customers, x.nm_customers
            order by x.vcurrency
            ) as z
            where z.total > 0 and z.sisa > 0
            $where
            order by z.nm_customers asc");
    }

    public static function getTop20PriceCheckProducts($start_date, $end_date)
    {
        return DB::select("SELECT p.id_product, p.code_product, p.nm_product, COUNT(pp.id_product) AS check_count
        FROM m_product_price_history_search pp
        LEFT JOIN m_product p ON pp.id_product = p.id_product
        WHERE pp.date_create >= ? AND pp.date_create <= ?
        GROUP BY p.id_product, p.code_product, p.nm_product
        ORDER BY check_count DESC
        LIMIT 20", [$start_date, $end_date]);
    }

    public static function getTopProductsQuotations($start_date, $end_date)
    {
        return DB::select("SELECT 
            p.nm_product, 
            p.code_product,
            COUNT(*) as total_quotations
        FROM 
            tb_so_dtl d 
        INNER JOIN 
            tb_so_hdr h 
        ON 
            h.id_so = d.id_so
        LEFT JOIN
            m_product p
        ON
            d.id_product = p.id_product
        WHERE 
            h.date_so BETWEEN ? AND ?
        GROUP BY 
            p.id_product, p.nm_product, p.code_product
        ORDER BY 
            total_quotations DESC
        LIMIT 20", [$start_date, $end_date]);
    }

    public static function getQuotationAndSoStatistics($start_date, $end_date)
    {
        return DB::select("SELECT 
            SUM(CASE WHEN hdr.status_so = 'QUOTATION' THEN 1 ELSE 0 END) AS quotations,
            SUM(CASE WHEN hdr.status_so IN ('SALES ORDER', 'SALE TO INVOICE') THEN 1 ELSE 0 END) AS total_so,
            SUM(CASE WHEN hdr.status_so = 'CANCEL QUOTATION' THEN 1 ELSE 0 END) AS total_cancelled,
            SUM(CASE WHEN hdr.status_so IN ('QUOTATION', 'SALES ORDER', 'SALE TO INVOICE', 'CANCEL QUOTATION') THEN 1 ELSE 0 END) AS total_q
        FROM 
            tb_so_hdr AS hdr
        WHERE 
            hdr.date_so BETWEEN ? AND ?", [$start_date, $end_date]);
    }

    public static function getDoOutstanding($tanggal)
    {
        return DB::select("select
	x.*
from
	(
	select
		a.id_do,
		a.code_do,
		a.date_do,
		a.date_delivery,
		c.date_pi,
		CASE
			WHEN a.date_delivery >= c.date_faktur THEN c.date_faktur
			when c.date_faktur <= a.date_delivery then c.date_faktur
			when c.date_faktur = ? then c.date_faktur
			when ? >= c.date_faktur then c.date_faktur
			ELSE NULL
		END AS date_faktur,
		case
			when c.date_faktur = ? then c.date_faktur
			when c.date_faktur <= a.date_delivery and a.date_delivery = ? then c.date_faktur
			else null
		end as cek_muncul,
		c.date_faktur as aa,
		d.nm_customers
	from
		tb_do_hdr a
	left join tb_so_hdr b on
		(a.id_so = b.id_so )
	left join tb_invoice_hdr c on
		(b.id_so = c.id_so)
	left join m_customers d on
		(b.id_customers = d.id_customers)
	where
		a.date_delivery <= ?
		and a.date_do >= '2024-01-01'
	) as x
where
		x.date_faktur is null
	or x.cek_muncul is not null
order by
	x.date_do asc", [$tanggal, $tanggal, $tanggal, $tanggal, $tanggal]);
    }
}
