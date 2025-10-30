<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

class Team extends Model
{
    use SoftDeletes, HasUuid, LogUsers;

    protected $fillable = [
        'name'
    ];

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}
