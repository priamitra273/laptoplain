<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;

class Team extends Model
{
    use HasUuid, LogUsers, SoftDeletes;

    protected $fillable = [
        'name',
    ];

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    #[Scope]
    protected function filterByUserRole(Builder $query): void
    {
        if (! Auth::user()->is_super_admin) {
            $query->where('name', '!=', 'Admin');
        }
    }
}
