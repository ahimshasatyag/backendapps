<?php

namespace App\Models\logbookcustomers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class Logbookcustomers extends Model
{
    /**
     * Get data untuk list log book 
     */
    public static function getData()
    {
        return DB::table('tb_log_book_customers as a')
            ->select('a.id_log_book', 'b.nm_customers', 'c.nm_users', 'a.date_log_book')
            ->join('m_customers as b', 'a.id_customers', '=', 'b.id_customers')
            ->join('m_users as c', 'a.username', '=', 'c.username')
            ->where('a.status_log_book', 'Log Book')
            ->get();
    }

    /**
     * Insert data baru 
     */
    public static function insertData($id_customers, $date_log_book, $masalah, $solusi, $catatan, $username)
    {
        $data = [
            'id_customers' => $id_customers,
            'date_log_book' => $date_log_book,
            'masalah' => $masalah,
            'solusi' => $solusi,
            'catatan' => $catatan,
            'username' => $username,
            'date_create' => Carbon::now(),
            'status_log_book' => 'Log Book',
        ];

        return DB::table('tb_log_book_customers')->insertGetId($data);
    }

    /**
     * Update data
     */
    public static function updateData($id_log_book, $id_customers, $date_log_book, $masalah, $solusi, $catatan, $username)
    {
        $data = [
            'id_customers' => $id_customers,
            'date_log_book' => $date_log_book,
            'masalah' => $masalah,
            'solusi' => $solusi,
            'catatan' => $catatan,
            'date_update' => Carbon::now(),
        ];

        return DB::table('tb_log_book_customers')
            ->where('id_log_book', $id_log_book)
            ->update($data);
    }

    /**
     * Delete / Cancel data 
     */
    public static function deleteData($id_log_book)
    {
        $data = [
            'date_update' => Carbon::now(), // Menggunakan date_update untuk mencatat waktu cancel
            'status_log_book' => 'CANCELED',
        ];

        return DB::table('tb_log_book_customers')
            ->where('id_log_book', $id_log_book)
            ->update($data);
    }

    /**
     * Get data customers 
     */
    public static function getDataCustomers()
    {
        return DB::table('m_customers')->get();
    }

    /**
     * Get data header by ID 
     */
    public static function getDataHeader($id_log_book)
    {
        return DB::table('tb_log_book_customers')->where('id_log_book', $id_log_book)->first();
    }
}
