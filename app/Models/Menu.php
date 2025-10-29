<?php

namespace App\Models;

use App\Observers\MenuObserver;
use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

#[ObservedBy(MenuObserver::class)]
class Menu extends Model
{
    use SoftDeletes, HasUuid, HasPermissions;

    protected $guard_name = 'web';

    protected $fillable = [
        'parent_id',
        'label',
        'icon',
        'route_name',
        'sequence_number',
        'is_active',
    ];

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }
}
