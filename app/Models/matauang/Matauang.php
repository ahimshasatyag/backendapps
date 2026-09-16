<?php

namespace App\Models\matauang;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Matauang extends Model
{
    /**
     * Mengambil data kurs terbaru untuk semua mata uang
     */
    public static function kurs()
    {
        return DB::select("SELECT mata_uang, kurs, kurs_pembulatan, date_create 
        FROM m_kurs
        WHERE (mata_uang, date_create) IN (
            SELECT mata_uang, MAX(date_create)
            FROM m_kurs
            GROUP BY mata_uang
        )
        ORDER BY mata_uang ASC");
    }

    /**
     * Mengambil data kurs terbaru untuk mata uang tertentu
     */
    public static function getKurs($mata_uang)
    {
        return DB::select("SELECT mata_uang, kurs, kurs_pembulatan, date_create 
        FROM m_kurs
        WHERE (mata_uang, date_create) IN (
            SELECT mata_uang, MAX(date_create)
            FROM m_kurs
            GROUP BY mata_uang
        )
        AND mata_uang = ?
        ORDER BY mata_uang ASC", [$mata_uang]);
    }
}
