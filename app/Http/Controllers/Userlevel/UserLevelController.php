<?php

namespace App\Http\Controllers\Userlevel;

use App\Http\Controllers\Controller;
use App\Models\Userlevel\UserLevel;
use App\Models\Userlevel\UserRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserLevelController extends Controller
{
    // index (data)
    public function index()
    {
        $levels = UserLevel::all();

        return response()->json([
            'status' => 'success',
            'data' => $levels
        ], 200);
    }

    // support data for create/edit (tambah, edit)
    public function supportData()
    {
        $menus = DB::table('m_menu')->get();
        $powers = DB::table('m_users_power')->get();
        $dashboards = DB::table('m_dashboard')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'menus' => $menus,
                'powers' => $powers,
                'dashboards' => $dashboards
            ]
        ], 200);
    }

    // simpan
    public function store(Request $request)
    {
        $request->validate([
            'nm_users_level' => 'required|string',
            'id_dashboard' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $maxId = UserLevel::max('id_users_level');
            $id_users_level = $maxId ? $maxId + 1 : 1;

            $level = UserLevel::create([
                'id_users_level' => $id_users_level,
                'nm_users_level' => $request->nm_users_level,
                'id_dashboard' => $request->id_dashboard,
                'date_create' => now(),
            ]);

            $duallistbox_role = $request->input('duallistbox_role', []);
            if (is_array($duallistbox_role)) {
                foreach ($duallistbox_role as $row) {
                    $id_menu = substr($row, 0, -1);
                    $id_users_power = substr($row, -1);
                    UserRole::create([
                        'id_menu' => $id_menu,
                        'id_users_power' => $id_users_power,
                        'id_users_level' => $id_users_level
                    ]);
                }
            }

            Log::info("Simpan Data Level Kode : " . $id_users_level);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'kode' => $id_users_level,
                'message' => 'Data berhasil disimpan'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    // edit
    public function show($id)
    {
        $level = UserLevel::where('id_users_level', $id)->first();
        if (!$level) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $roles = UserRole::where('id_users_level', $id)->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'level' => $level,
                'roles' => $roles
            ]
        ], 200);
    }

    // update
    public function update(Request $request, $id)
    {
        $request->validate([
            'nm_users_level' => 'required|string',
            'id_dashboard' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $level = UserLevel::where('id_users_level', $id)->first();
            if (!$level) {
                return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
            }

            $level->update([
                'nm_users_level' => $request->nm_users_level,
                'id_dashboard' => $request->id_dashboard,
                'date_update' => now(),
            ]);

            UserRole::where('id_users_level', $id)->delete();

            $duallistbox_role = $request->input('duallistbox_role', []);
            if (is_array($duallistbox_role)) {
                foreach ($duallistbox_role as $row) {
                    $id_menu = substr($row, 0, -1);
                    $id_users_power = substr($row, -1);
                    UserRole::create([
                        'id_menu' => $id_menu,
                        'id_users_power' => $id_users_power,
                        'id_users_level' => $id
                    ]);
                }
            }

            Log::info("Update Data Level Kode : " . $id);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'kode' => $id,
                'message' => 'Data berhasil diupdate'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengupdate data: ' . $e->getMessage()
            ], 500);
        }
    }

    // delete
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $level = UserLevel::where('id_users_level', $id)->first();
            if (!$level) {
                return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
            }

            UserRole::where('id_users_level', $id)->delete();
            $level->delete();

            Log::info("Hapus Data Level Kode : " . $id);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Data berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ], 500);
        }
    }
}
