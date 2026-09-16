<?php

namespace App\Models\cekserialnumber;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Cekserialnumber extends Model
{
    /**
     * Get data untuk datatables 
     */
    public static function getData()
    {
        return DB::table('tb_afs_csr as a')
            ->select(
                'a.barcode',
                'b.nm_product',
                'b.code_product',
                'g.nm_customers'
            )
            ->leftJoin('m_product as b', 'a.id_product', '=', 'b.id_product')
            ->leftJoin('m_customers as g', 'a.id_customers', '=', 'g.id_customers')
            ->whereNotNull('a.barcode')
            ->where('a.barcode', '!=', '')
            ->get();
    }

    /**
     * Get data serial number
     */
    public static function getDataSerialNumber()
    {
        return DB::table('tb_afs_csr')
            ->select('barcode')
            ->whereNotNull('barcode')
            ->where('barcode', '!=', '')
            ->orderBy('barcode', 'ASC')
            ->get();
    }

    /**
     * Get detail serial number
     */
    public static function getDetailSerialNumber($barcode)
    {
        return DB::table('tb_afs_csr as a')
            ->select(
                'b.code_product',
                'b.nm_product',
                'b.product_deskripsi',
                'g.nm_customers',
                'g.customers_address',
                'h.nama as provinsi',
                'i.nama_kabupaten as kabupaten',
                'g.customers_phone',
                'g.customers_mobile',
                'a.do_code',
                'a.waranty_start',
                'a.waranty_time',
                'a.waranty_end'
            )
            ->leftJoin('m_product as b', 'a.id_product', '=', 'b.id_product')
            ->leftJoin('m_customers as g', 'a.id_customers', '=', 'g.id_customers')
            ->leftJoin('m_provinsi as h', 'g.provinsi', '=', 'h.id')
            ->leftJoin('m_kota as i', 'g.kabupaten', '=', 'i.id')
            ->where('a.barcode', $barcode)
            ->get();
    }

    /**
     * Get history services
     */
    public static function getHistoryServices($barcode)
    {
        $query = DB::table('tb_afs_csr as a')
            ->select(
                'b.cst_code',
                'b.cst_date',
                'b.status',
                'c.description as catatan_kerusakan',
                'c.lkt_code',
                'c.id_afs_lkt'
            )
            ->addSelect(DB::raw('(SELECT COUNT(lkt_sub_code) FROM tb_afs_realisasi WHERE id_afs_lkt = c.id_afs_lkt) as total_realisasi'))
            ->addSelect(DB::raw('(SELECT lap_penyelesain FROM tb_afs_realisasi WHERE id_afs_lkt = c.id_afs_lkt ORDER BY actual_starting_date DESC, lkt_sub_code DESC LIMIT 1) as laporan_akhir'))
            ->join('tb_afs_cst as b', 'a.id_afs_csr', '=', 'b.id_afs_csr')
            ->join('tb_afs_lkt as c', 'b.id_afs_cst', '=', 'c.id_afs_cst')
            ->where('a.barcode', $barcode)
            ->orderBy('b.cst_date', 'DESC')
            ->get();

        foreach ($query as $row) {
            $teknisi_names = [];

            $q_teknisi1 = DB::table('tb_afs_realisasi_teknisi as rt')
                ->leftJoin('m_karyawan as mk', 'mk.id_karyawan', '=', 'rt.id_karyawan')
                ->where('rt.id_afs_lkt', $row->id_afs_lkt)
                ->whereNotNull('rt.id_karyawan')
                ->where('rt.id_karyawan', '>', 0)
                ->select('mk.nm_karyawan');

            $q_teknisi2 = DB::table('tb_afs_realisasi_teknisi as rt')
                ->leftJoin('tb_afs_realisasi as r', 'r.lkt_sub_code', '=', 'rt.lkt_sub_code')
                ->leftJoin('m_karyawan as mk', 'mk.id_karyawan', '=', 'rt.actual_id_karyawan')
                ->where('rt.id_afs_lkt', $row->id_afs_lkt)
                ->where(function ($query) {
                    $query->whereNull('rt.id_karyawan')
                          ->orWhere('rt.id_karyawan', '=', 0);
                })
                ->select('mk.nm_karyawan');

            $q_teknisi_result = $q_teknisi1->union($q_teknisi2)->get();

            foreach ($q_teknisi_result as $t) {
                if ($t->nm_karyawan) {
                    $teknisi_names[] = $t->nm_karyawan;
                }
            }

            $row->teknisi = implode(", ", array_unique($teknisi_names));
        }

        return $query;
    }
}
