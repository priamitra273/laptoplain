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

    protected static function booted()
    {
        static::deleting(function ($member) {
            // Ambil semua task dari project
            $tasks = Task::where('project_id', $member->project_id)->get();

            // Detach user dari setiap task
            foreach ($tasks as $task) {
                $task->users()->detach($member->user_id);
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(MsProjectRole::class, 'project_role_id');
    }

    public function owned(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owned_id');
    }
}
