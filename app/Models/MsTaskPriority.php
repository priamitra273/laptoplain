<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsTaskPriority extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ms_task_priorities';

    protected $fillable = [
        'name',
        'severity',
        'owned_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /**
     * Relasi ke user (owner)
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'owned_id');
    }

    /**
     * Relasi ke user pembuat
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke user pengubah
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Relasi ke user penghapus
     */
    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
