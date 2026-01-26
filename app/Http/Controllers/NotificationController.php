<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
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
                    'id' => $notification['id'],
                    'message' => $notification['message'],
                    'task_id' => $notification['task_id'],
                    'is_read' => $userPivot['is_read'],
                ];
            });
        
        return response()->json(Sqids::rec_encode_ids_in_list($notifications));
    }

    public function stream()
    {
        set_time_limit(0);

        return response()->stream(function () {
            while (ob_get_level() > 0) {
                ob_end_flush();
            }
            ob_implicit_flush(true);

            $userId = Auth::id();
            $lastId = 0;

            // 1️⃣ Initial dump
            $initial = Notification::whereHas(
                'users',
                fn($q) =>
                $q->where('user_id', $userId)
            )
                ->with([
                    'users' => fn($q) => $q->where('user_id', Auth::id())->withPivot('is_read'),
                    // 'status',
                    // 'type'
                ])->latest()->get();
            
            $formattedInitial = $initial->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'message' => $notification->message,
                    'task_id' => $notification->task_id,
                    // 'task_status' => $notification->status,
                    // 'task_type' => $notification->type,
                    'is_read' => $notification->users->first()->pivot->is_read,
                ];
            });

            echo "event: init\n";
            echo "data: " . json_encode(Sqids::rec_encode_ids_in_list($formattedInitial)) . "\n\n";
            flush();

            $lastId = $initial->max('id') ?? 0;

            // 2️⃣ Stream loop
            while (!connection_aborted()) {

                $new = Notification::where('id', '>', $lastId)
                    ->whereHas(
                        'users',
                        fn($q) =>
                        $q->where('user_id', $userId)
                    )
                    ->with([
                        'users' => fn($q) => $q->where('user_id', Auth::id())->withPivot('is_read'),
                        // 'status',
                        // 'type'
                    ])
                    ->orderBy('id')
                    ->get();
                
                $formattedNew = $new->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'message' => $notification->message,
                        'task_id' => $notification->task_id,
                        // 'task_status' => $notification->status,
                        // 'task_type' => $notification->type,
                        'is_read' => $notification->users->first()->pivot->is_read,
                    ];
                });

                $sentNotification = false;

                foreach ($formattedNew as $n) {
                    echo "event: notification\n";
                    echo "data: " . json_encode(Sqids::rec_encode_ids_in_list($n)) . "\n\n";

                    $lastId = $n['id'];
                    $sentNotification = true;
                }

                if (! $sentNotification) {
                    echo "event: ping\n";
                    echo "data: {}\n\n";
                }

                flush();

                sleep(10); // throttle
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'Content-Encoding' => 'none',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function markAsRead(string $encoded)
    {
        $id = Sqids::decode($encoded);
        $notification = Notification::findOrFail($id);
        $notification->users()->updateExistingPivot(Auth::id(), ['is_read' => true]);
        return response()->json(['success' => true]);
    }

    public function clearAll()
    {
        $user = Auth::user();
        $user->notifications()->detach();
        // $user->notifications()->updateExistingPivot($user->notifications->pluck('id')->toArray(), ['is_read' => true]);

        return response()->json(['success' => true]);
    }
}
