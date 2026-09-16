<?php

namespace App\Models\sop;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Sop extends Model
{
    /**
     * Get data untuk datatables 
     */
    public static function getData()
    {
        return DB::table('tb_sop_hdr as a')
            ->select('a.id_sop', 'b.nm_karyawan_divisi', 'a.code_sop', 'a.nm_sop', 'a.date_create', 'c.nm_users', 'a.file_pdf')
            ->join('m_karyawan_divisi as b', 'a.divisi', '=', 'b.id_karyawan_divisi') // Menggunakan 'divisi' sesuai dengan query insert Cform
            ->join('m_users as c', 'a.username_create', '=', 'c.username')
            ->get();
    }

    /**
     * Get data divisi berdasarkan username 
     */
    public static function getDataDivisi($username)
    {
        $cek_data = DB::table('m_users as a')
            ->join('m_karyawan as b', 'a.id_karyawan', '=', 'b.id_karyawan')
            ->join('m_karyawan_divisi as c', 'b.id_karyawan_divisi', '=', 'c.id_karyawan_divisi')
            ->select('a.username', 'b.id_karyawan_divisi', 'c.nm_karyawan_divisi')
            ->where('a.username', $username)
            ->first();

        if ($cek_data) {
            return DB::table('m_karyawan_divisi')
                ->where('id_karyawan_divisi', $cek_data->id_karyawan_divisi)
                ->get();
        } else {
            return DB::table('m_karyawan_divisi')->get();
        }
    }

    /**
     * Insert data SOP 
     */
    public static function insertData($divisi, $code_sop, $nm_sop, $file_pdf, $username)
    {
        $data = [
            'divisi' => $divisi,
            'code_sop' => $code_sop,
            'nm_sop' => $nm_sop,
            'file_pdf' => $file_pdf,
            'username_create' => $username,
            'date_create' => Carbon::now(),
            'status' => 'DRAFT'
        ];

        return DB::table('tb_sop_hdr')->insertGetId($data);
    }

    /**
     * Get data header by ID
     */
    public static function getDataHeader($id_sop)
    {
        return DB::table('tb_sop_hdr')->where('id_sop', $id_sop)->first();
    }

    /**
     * Update data SOP 
     */
    public static function updateData($id_sop, $code_sop, $nm_sop, $file_pdf, $status, $username)
    {
        $data = [
            'code_sop' => $code_sop,
            'nm_sop' => $nm_sop,
            'username_update' => $username,
            'date_update' => Carbon::now(),
        ];

        if ($status) {
            $data['status'] = $status;
        }

        if ($file_pdf != null) {
            $data['file_pdf'] = $file_pdf;
        }

        return DB::table('tb_sop_hdr')->where('id_sop', $id_sop)->update($data);
    }

    /**
     * Get semua data SOP berdasarkan divisi 
     */
    public static function bacaSemua($divisi)
    {
        return DB::table('tb_sop_hdr')
            ->where('divisi', $divisi)
            ->where('status', '!=', 'HISTORY')
            ->orderBy('code_sop', 'asc')
            ->get();
    }

    /**
     * Get data history 
     */
    public static function getDataHistory($id_sop)
    {
        return DB::table('tb_sop_hdr')->where('id_sop_history', $id_sop)->get();
    }

    /**
     * Insert history revisi
     */
    public static function insertHistory($id_sop, $username)
    {
        $data_header = self::getDataHeader($id_sop);

        if ($data_header) {
            $data = [
                'divisi' => $data_header->divisi,
                'code_sop' => $data_header->code_sop,
                'nm_sop' => $data_header->nm_sop,
                'file_pdf' => $data_header->file_pdf,
                'username_create' => $data_header->username_create,
                'date_create' => $data_header->date_create,
                'username_update' => $username,
                'date_update' => Carbon::now(),
                'status' => 'HISTORY',
                'id_sop_history' => $id_sop
            ];

            return DB::table('tb_sop_hdr')->insert($data);
        }
        return false;
    }

    /**
     * Update status SOP
     */
    public static function updateStatus($id_sop, $status_sop)
    {
        return DB::table('tb_sop_hdr')->where('id_sop', $id_sop)->update(['status' => $status_sop]);
    }
}
