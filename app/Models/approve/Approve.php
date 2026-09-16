<?php

namespace App\Models\approve;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Approve extends Model
{
    /**
     * Mengambil semua data quotations (mirip bacasemua_quotations)
     */
    public static function getQuotations($id_users_level, $search = null)
    {
        $query = DB::table('tb_approval as a')
            ->join('m_users as b', 'a.username_request', '=', 'b.username')
            ->join('m_approve_dtl as c', 'a.id_approve', '=', 'c.id_approve')
            ->join('tb_so_hdr as d', 'a.id_key_table', '=', 'd.id_so')
            ->join('m_customers as e', 'd.id_customers', '=', 'e.id_customers')
            ->join('m_karyawan as f', 'd.id_karyawan', '=', 'f.id_karyawan')
            ->join('m_type_bayar_hdr as g', 'd.id_type_pembayaran', '=', 'g.id_type_pembayaran')
            ->join('m_waktu_bayar as h', 'd.id_waktu_bayar', '=', 'h.id_waktu_bayar')
            ->where('c.id_users_level', $id_users_level)
            ->where('a.status_approve', 0)
            ->whereNotIn('a.id_approve', [8, 9, 10])
            ->select(
                'a.id_approval', 'a.id_key_table', 'a.action_approve', 'a.action_canceled', 'a.date_request', 
                'b.nm_users', 'a.nm_module', 'a.code_key_table', 'a.alasan', 
                'd.code_so', 'e.nm_customers', 'f.nm_karyawan', 'g.nm_type_pembayaran', 
                'd.ndp_persen', 'd.ndp_amount', 'd.ntenor', 'd.ntenor_amount', 'h.nm_waktu_bayar', 'd.internal_notes'
            );

        if ($search) {
            $query->where(function ($q) use ($search) {
                $terms = explode(',', $search);
                foreach ($terms as $term) {
                    $term = trim($term);
                    $q->orWhere('d.code_so', 'like', "%$term%")
                      ->orWhere('e.nm_customers', 'like', "%$term%")
                      ->orWhere('f.nm_karyawan', 'like', "%$term%");
                }
            });
        }

        return $query->orderBy('a.date_request', 'desc')->get();
    }

    /**
     * Mengambil data accounting (mirip bacasemua_accounting)
     */
    public static function getAccounting($id_users_level, $search = null)
    {
        $query = DB::table('tb_approval as a')
            ->join('m_users as b', 'a.username_request', '=', 'b.username')
            ->join('m_approve_dtl as c', 'a.id_approve', '=', 'c.id_approve')
            ->where('c.id_users_level', $id_users_level)
            ->where('a.status_approve', 0)
            ->whereIn('a.id_approve', [8, 9, 10])
            ->select('a.id_approval', 'a.action_approve', 'a.action_canceled', 'a.date_request', 'b.nm_users', 'a.nm_module', 'a.code_key_table', 'a.alasan');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $terms = explode(',', $search);
                foreach ($terms as $term) {
                    $term = trim($term);
                    $q->orWhere('a.id_approval', 'like', "%$term%")
                      ->orWhere('a.action_approve', 'like', "%$term%");
                }
            });
        }

        return $query->orderBy('a.date_request', 'desc')->get();
    }

    /**
     * Mengambil data history (mirip bacasemua_history)
     */
    public static function getHistory($id_users_level, $search = null)
    {
        $query = DB::table('tb_approval as a')
            ->join('m_users as b', 'a.username_request', '=', 'b.username')
            ->join('m_approve_dtl as c', 'a.id_approve', '=', 'c.id_approve')
            ->where('c.id_users_level', $id_users_level)
            ->where('a.status_approve', 1)
            ->select('a.id_approval', 'a.action_approve', 'a.action_canceled', 'a.date_request', 'b.nm_users', 'a.nm_module', 'a.code_key_table', 'a.alasan', 'a.action_dipilih');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $terms = explode(',', $search);
                foreach ($terms as $term) {
                    $term = trim($term);
                    $q->orWhere('a.id_approval', 'like', "%$term%")
                      ->orWhere('a.action_approve', 'like', "%$term%");
                }
            });
        }

        return $query->orderBy('a.date_request', 'desc')->limit(100)->get();
    }

    /**
     * Proses approve update (mirip approve di Mmaster)
     */
    public static function processApprove($id_approval, $status, $aksi, $username)
    {
        $approval = DB::table('tb_approval')->where('id_approval', $id_approval)->first();
        if ($approval) {
            $nm_table = $approval->nm_table;
            $key_table = $approval->key_table;
            $id_key_table = $approval->id_key_table;
            $status_table = $approval->status_table;

            DB::table($nm_table)->where($key_table, $id_key_table)->update([
                $status_table => $status
            ]);

            DB::table('tb_approval')
                ->where('id_approval', $id_approval)
                ->where('status_approve', '0')
                ->update([
                    'date_approve' => now(),
                    'username_approve' => $username,
                    'status_approve' => '1',
                    'action_dipilih' => $aksi,
                ]);

            return true;
        }
        return false;
    }

    public static function getApproval($id_approval)
    {
        return DB::table('tb_approval')->where('id_approval', $id_approval)->first();
    }
}
