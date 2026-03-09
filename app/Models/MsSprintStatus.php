<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MsSprintStatus extends Model
{
    protected $table = 'ms_sprint_statuses';
    protected $fillable = ['name', 'severity'];

    public function sprints()
    {
        return $this->hasMany(ProjectSprint::class, 'sprint_status_id');
    }


    public static function planning(): self
    {
        return static::where('name', 'Planning')->firstOrFail();
    }
    public static function active(): self
    {
        return static::where('name', 'Active')->firstOrFail();
    }
    public static function completed(): self
    {
        return static::where('name', 'Completed')->firstOrFail();
    }
}
