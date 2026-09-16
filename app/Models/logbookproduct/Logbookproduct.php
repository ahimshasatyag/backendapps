<?php

namespace App\Models\logbookproduct;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Logbookproduct extends Model
{
    /**
     * Get data untuk list log book 
     */
    public static function getData()
    {
        return DB::table('tb_log_book_product as a')
            ->select('a.id_log_book', 'b.code_product', 'b.nm_product', 'c.nm_users', 'a.date_log_book')
            ->join('m_product as b', 'a.id_product', '=', 'b.id_product')
            ->join('m_users as c', 'a.username', '=', 'c.username')
            ->where('a.status_log_book', 'Log Book')
            ->get();
    }

    /**
     * Insert data baru
     */
    public static function insertData($id_product, $id_type_kerusakan, $date_log_book, $masalah, $solusi, $catatan, $username)
    {
        $data = [
            'id_product' => $id_product,
            'date_log_book' => $date_log_book,
            'masalah' => $masalah,
            'solusi' => $solusi,
            'catatan' => $catatan,
            'username' => $username,
            'date_create' => Carbon::now(),
            'status_log_book' => 'Log Book',
            'id_type_kerusakan' => $id_type_kerusakan,
        ];

        return DB::table('tb_log_book_product')->insertGetId($data);
    }

    /**
     * Update data
     */
    public static function updateData($id_log_book, $id_product, $id_type_kerusakan, $date_log_book, $masalah, $solusi, $catatan, $username)
    {
        $data = [
            'id_product' => $id_product,
            'date_log_book' => $date_log_book,
            'masalah' => $masalah,
            'solusi' => $solusi,
            'catatan' => $catatan,
            'date_update' => Carbon::now(),
            'id_type_kerusakan' => $id_type_kerusakan
        ];

        return DB::table('tb_log_book_product')
            ->where('id_log_book', $id_log_book)
            ->update($data);
    }

    /**
     * Delete / Cancel data 
     */
    public static function deleteData($id_log_book)
    {
        $data = [
            'date_update' => Carbon::now(), 
            'status_log_book' => 'CANCELED',
        ];

        return DB::table('tb_log_book_product')
            ->where('id_log_book', $id_log_book)
            ->update($data);
    }

    /**
     * Get data barang 
     */
    public static function getDataBarang()
    {
        return DB::table('m_product')->get();
    }

    /**
     * Get data tipe kerusakan 
     */
    public static function getDataTypeKerusakan()
    {
        return DB::table('m_type_kerusakan')->get();
    }

    /**
     * Get data header by ID 
     */
    public static function getDataHeader($id_log_book)
    {
        return DB::table('tb_log_book_product')->where('id_log_book', $id_log_book)->first();
    }
}
