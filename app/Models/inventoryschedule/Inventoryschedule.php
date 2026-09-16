<?php

namespace App\Models\inventoryschedule;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Inventoryschedule extends Model
{
    /**
     * Get list of inventory schedule for datatables/index
     */
    public static function getList($perPage, $search = null)
    {
        $query = DB::table('inventory_schedule as a')
            ->join('inventory_assets as b', 'a.asset_id', '=', 'b.id')
            ->select('a.id', 'b.name as asset_name', 'a.name', 'a.periode', 'a.due_date');

        if ($search) {
            $query->where(function($q) use ($search) {
                $terms = explode(',', $search);
                foreach ($terms as $term) {
                    $term = trim($term);
                    $q->orWhere('a.name', 'like', "%$term%")
                      ->orWhere('b.name', 'like', "%$term%")
                      ->orWhere('a.periode', 'like', "%$term%");
                }
            });
        }
        
        return $query->orderBy('a.id', 'desc')->paginate($perPage);
    }

    /**
     * Get data assets
     */
    public static function getAssets()
    {
        return DB::table('inventory_assets')->get();
    }

    /**
     * Get data users
     */
    public static function getUsers()
    {
        return DB::table('m_users')->get();
    }
    
    /**
     * Get schedule PICs by schedule ID
     */
    public static function getSchedulePic($schedule_id)
    {
        return DB::table('inventory_schedule_pic')->where('inventory_schedule_id', $schedule_id)->get();
    }
    
    /**
     * Get specific schedule by ID
     */
    public static function getDetail($id)
    {
        return DB::table('inventory_schedule')->where('id', $id)->first();
    }
}
