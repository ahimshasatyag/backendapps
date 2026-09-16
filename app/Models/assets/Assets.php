<?php

namespace App\Models\assets;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Assets extends Model
{
    /**
     * Get list of inventory assets for datatables/index
     */
    public static function getList($perPage, $search = null)
    {
        $query = DB::table('inventory_assets as a')
            ->join('inventory_category as b', 'a.inventory_category_id', '=', 'b.id')
            ->select('a.id', 'a.name', 'b.name as category_name', 'a.status');

        if ($search) {
            $query->where(function($q) use ($search) {
                $terms = explode(',', $search);
                foreach ($terms as $term) {
                    $term = trim($term);
                    $q->orWhere('a.name', 'like', "%$term%")
                      ->orWhere('b.name', 'like', "%$term%");
                }
            });
        }
        
        return $query->orderBy('a.id', 'desc')->paginate($perPage);
    }

    /**
     * Get data assets type
     */
    public static function getTypes()
    {
        return DB::table('inventory_type')->get();
    }

    /**
     * Get data assets category
     */
    public static function getCategories()
    {
        return DB::table('inventory_category')->get();
    }

    /**
     * Get data asset
     */
    public static function getAssets()
    {
        return DB::table('inventory_assets')->get();
    }

    /**
     * Get specific asset by ID
     */
    public static function getAssetById($id)
    {
        return DB::table('inventory_assets as a')
            ->join('inventory_category as b', 'a.inventory_category_id', '=', 'b.id')
            ->join('inventory_type as c', 'a.inventory_type_id', '=', 'c.id')
            ->select('a.*', 'a.name', 'b.name as category_name', 'c.name as type_name')
            ->where('a.id', $id)
            ->first();
    }

    /**
     * Get serial numbers for an asset
     */
    public static function getSerialNumbers($asset_id)
    {
        return DB::table('inventory_serial_number')->where('asset_id', $asset_id)->get();
    }
}
