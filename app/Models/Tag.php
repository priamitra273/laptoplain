<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tag extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tags';

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

    public function tasks()
    {
        return $this->morphedByMany(
            Task::class,
            'model',
            'taggables',
            'tag_id',
            'model_id'
        );
    }
}
