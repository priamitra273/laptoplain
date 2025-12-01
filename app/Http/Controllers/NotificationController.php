<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $notifications = Notification::with(['users' => function ($query) {
            $query->where('user_id', Auth::id());
        }])
            ->whereHas('users', function ($query) {
                $query->where('user_id', Auth::id());
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($notification) {
                // Ambil pivot dari user yang login
                $userPivot = $notification->users->first()->pivot;
                return [
                    'id' => $notification->id,
                    'message' => $notification->message,
                    'pivot' => $userPivot,
                ];
            });

        return response()->json($notifications);
    }

    public function markAsRead(Notification $notification)
    {
        $notification->users()->updateExistingPivot(Auth::id(), ['is_read' => true]);
        return response()->json(['success' => true]);
    }

    public function clearAll()
    {
        $user = Auth::user();

        // Opsi 1: Hapus semua pivot notifikasi untuk user
        $user->notifications()->detach();

        // Opsi 2: Jika ingin tetap menyimpan notifikasi tapi tandai semua sebagai dibaca
        // $user->notifications()->updateExistingPivot($user->notifications->pluck('id')->toArray(), ['is_read' => true]);

        return response()->json(['success' => true]);
    }
}
