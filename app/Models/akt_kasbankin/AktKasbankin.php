<?php

namespace App\Models\akt_kasbankin;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AktKasbankin extends Model
{
    protected $table = 'tb_kb_masuk_hdr';
    protected $primaryKey = 'id_kb_masuk';
    public $timestamps = false;

    /**
     * Get list of banks
     */
    public static function getBank()
    {
        return DB::table('m_bank')->get();
    }

    /**
     * Get list of COA
     */
    public static function getCoa()
    {
        return DB::table('m_coa')->get();
    }

    /**
     * Get COA by ID
     */
    public static function getCoaById($id)
    {
        return DB::table('m_coa')->where('id_coa', $id)->first();
    }

    /**
     * Get active SO from last year
     */
    public static function getSo()
    {
        $tanggal = date('Y-m-d', strtotime('-1 year'));
        return DB::table('tb_so_hdr as a')
            ->join('m_customers as b', 'a.id_customers', '=', 'b.id_customers')
            ->whereIn('a.status_so', ['SALE TO INVOICE', 'SALES ORDER'])
            ->where('a.date_so', '>=', $tanggal)
            ->select('a.*', 'b.nm_customers')
            ->get();
    }

    /**
     * Get SO details
     */
    public static function getSoDetail($id_so)
    {
        return DB::table('tb_so_hdr as a')
            ->join('m_customers as b', 'a.id_customers', '=', 'b.id_customers')
            ->where('a.id_so', $id_so)
            ->select('a.ndp_amount', 'b.nm_customers', 'a.code_so')
            ->get();
    }

    /**
     * Get list of KB Masuk (bacasemua)
     */
    public static function getList($perPage, $search = null)
    {
        $query = DB::table('tb_kb_masuk_hdr');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $terms = explode(',', $search);
                foreach ($terms as $term) {
                    $term = trim($term);
                    $q->orWhere('code_kb_masuk', 'like', "%$term%")
                      ->orWhere('deskripsi', 'like', "%$term%");
                }
            });
        }

        // Return paginated results, equivalent to bacasemua
        return $query->orderBy('date_create', 'desc')->paginate($perPage);
    }
}
