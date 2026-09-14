<?php

namespace App\Http\Controllers\Userlog;

use App\Http\Controllers\Controller;
use App\Models\Userlog\UserLog;
use Illuminate\Http\Request;
use Carbon\Carbon;

class UserLogController extends Controller
{
    public function index()
    {
        $logs = UserLog::select('username', 'activity', 'ip_address', 'd_createdate')
            ->orderBy('d_createdate', 'desc')
            ->limit(500)
            ->get()
            ->map(function ($log) {
                return [
                    'username' => $log->username,
                    'activity' => $log->activity,
                    'ip_address' => $log->ip_address,
                    'd_createdate' => $log->d_createdate ? Carbon::parse($log->d_createdate)->format('d-m-Y H:i:s') : null,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => $logs
        ], 200);
    }
}
