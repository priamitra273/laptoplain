<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Taggable extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'taggables';

    protected $fillable = [
        'model_type',
        'model_id',
        'tag_id',
        'owned_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }


    public function model()
    {
        return $this->morphTo();
    }


    public function owner()
    {
        return $this->belongsTo(User::class, 'owned_id');
    }
}
