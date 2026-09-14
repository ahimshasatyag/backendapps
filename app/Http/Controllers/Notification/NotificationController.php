<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $query = Notification::query();

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('id_users_level')) {
            $query->where('id_users_level', $request->id_users_level);
        }
        
        if ($request->has('is_read')) {
            $query->where('is_read', $request->is_read);
        }

        $notifications = $query->with(['user', 'userLevel'])
                               ->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $notifications
        ]);
    }

    public function show($id)
    {
        $notification = Notification::with(['user', 'userLevel'])->find($id);

        if (!$notification) {
            return response()->json([
                'status' => 'error',
                'message' => 'Notification not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $notification
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'id_users_level' => 'required|integer',
            'kode_trans' => 'required|string',
            'judul' => 'required|string|max:100',
            'pesan' => 'required|string',
            'action' => 'required|in:Create,Update,Delete',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        $notification = Notification::create([
            'user_id' => $request->user_id,
            'id_users_level' => $request->id_users_level,
            'kode_trans' => $request->kode_trans,
            'judul' => $request->judul,
            'pesan' => $request->pesan,
            'action' => $request->action,
            'is_read' => 0,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Notification created successfully',
            'data' => $notification
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'status' => 'error',
                'message' => 'Notification not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'is_read' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }

        if ($request->has('is_read')) {
            $notification->is_read = $request->is_read;
        }

        $notification->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification updated successfully',
            'data' => $notification
        ]);
    }

    public function destroy($id)
    {
        $notification = Notification::find($id);

        if (!$notification) {
            return response()->json([
                'status' => 'error',
                'message' => 'Notification not found'
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification deleted successfully'
        ]);
    }
    
    public function markAllAsRead(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()
            ], 422);
        }
        
        Notification::where('user_id', $request->user_id)
            ->where('is_read', 0)
            ->update(['is_read' => 1]);
            
        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read'
        ]);
    }
}
