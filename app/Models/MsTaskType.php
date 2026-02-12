<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MsTaskType extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ms_task_types';

    protected $fillable = [
        'name',
        'severity',
        'owned_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];


    public function owner()
    {
        return $this->belongsTo(User::class, 'owned_id');
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }


    public function deleter()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
