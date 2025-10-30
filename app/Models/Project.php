<?php

namespace App\Models;

use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\MsProjectPriority;

class Project extends Model
{
    use SoftDeletes, LogUsers;

    protected $table = 'projects';

    protected $fillable = [
        'status_id',
        'priority_id',
        'owner_id',
        'owned_id',
        'emoji',
        'title',
        'description',
        'start_date',
        'due_date',
        'progress',
        'sequence_number',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'progress' => 'double',
    ];

    public function status()
    {
        return $this->belongsTo(MsProjectStatus::class, 'status_id');
    }

    public function priority()
    {
        return $this->belongsTo(MsProjectPriority::class, 'priority_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function owned()
    {
        return $this->belongsTo(User::class, 'owned_id');
    }

    public function projectMembers()
    {
        return $this->hasMany(ProjectMember::class, 'project_id')
    }
}
