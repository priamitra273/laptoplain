<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectMember extends Model
{
    use SoftDeletes, LogUsers;

    protected $table = 'project_members';

    protected $fillable = [
        'project_id',
        'user_id',
        'project_role_id',
        'owned_id',
        'is_active',
        'created_by',
        'updated_by',
        'deleted_by'
    ];

    protected $casts = [
        'id' => 'integer',
        'project_id' => 'integer',
        'user_id' => 'integer',
        'project_role_id' => 'integer',
        'owned_id' => 'integer',
        'is_active' => 'boolean'
    ];

    /**
     * Relasi ke project
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Relasi ke user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relasi ke project role
     */
    public function projectRole(): BelongsTo
    {
        return $this->belongsTo(MsProjectRole::class, 'project_role_id');
    }

    /**
     * Relasi ke user (owner)
     */
    public function owned(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owned_id');
    }
}