<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use LogUsers;

    protected $fillable = [
        'team_id',
        'label',
        'name',
        'guard_name',
        'is_active'
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
