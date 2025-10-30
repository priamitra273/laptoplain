<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationUser extends Model
{
    use HasFactory;

    protected $table = 'notification_users';

    protected $fillable = [
        'notification_id',
        'user_id',
        'is_read',
    ];

    /**
     * Relasi ke Notification
     */
    public function notification()
    {
        return $this->belongsTo(Notification::class, 'notification_id');
    }

    /**
     * Relasi ke User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
