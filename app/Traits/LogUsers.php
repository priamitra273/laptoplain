<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait LogUsers 
{
    public function created_by_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updated_by_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deleted_by_user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public static function bootLogUsers()
    {
        static::creating(function (Model $model) {
            if (empty($model->created_by)) {
                $model->created_by = Auth::id();
            }

            if (empty($model->updated_by)) {
                $model->updated_by = Auth::id();
            }
        });

        static::updating(function (Model $model) {
            if (empty($model->updated_by)) {
                $model->updated_by = Auth::id();
            }
        });

        static::deleting(function (Model $model) {
            if (empty($model->deleted_by)) {
                $model->deleted_by = Auth::id();
                $model->save();
            }
        });
    }
}