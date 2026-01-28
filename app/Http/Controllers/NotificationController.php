<?php

namespace App\Http\Controllers;

use App\Facades\Sqids;
use App\Models\Notification;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

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
            $key = "notifications:user:$userId";
            $PING_INTERVAL = 30;
            $lastPing = time();

            // Initial dump
            $initial = Notification::whereHas(
                'users',
                fn($q) => $q->where('user_id', $userId)
            )
                ->with([
                    'users' => fn($q) =>
                    $q->where('user_id', $userId)->withPivot('is_read'),
                ])
                ->latest()
                ->get()
                ->map(fn($n) => [
                    'id' => $n->id,
                    'message' => $n->message,
                    'task_id' => $n->task_id,
                    'is_read' => $n->users->first()->pivot->is_read,
                ]);


            echo "event: init\n";
            echo "data: " . json_encode(
                Sqids::rec_encode_ids_in_list($initial)
            ) . "\n\n";
            flush();

            // Loop Redis
            while (!connection_aborted()) {
                $cached = Cache::store('redis')->get($key, []);

                if (!empty($cached)) {
                    foreach ($cached as $notif) {
                        echo "event: notification\n";
                        echo "data: " . json_encode($notif) . "\n\n";
                    }

                    Cache::store('redis')->forget($key);

                    flush();
                    $lastPing = time();
                }

                // heartbeat
                if (time() - $lastPing >= $PING_INTERVAL) {
                    echo "event: ping\n";
                    echo "data: {}\n\n";
                    flush();


                    $lastPing = time();
                }
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
