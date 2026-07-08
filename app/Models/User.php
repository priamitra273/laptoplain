<?php

namespace App\Models;

use App\Observers\UserObserver;
use App\Traits\LogsActivityUser;
use App\Traits\LogUsers;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\Contracts\OAuthenticatable;
use Laravel\Passport\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Concerns\HasUuid;
use Spatie\Permission\Traits\HasRoles;

#[ObservedBy(UserObserver::class)]
class User extends Authenticatable implements HasMedia, OAuthenticatable
{
    use HasApiTokens, InteractsWithMedia, LogsActivityUser, LogUsers, SoftDeletes;
    use HasFactory, HasRoles, HasUuid, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_active',
        'created_by',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function createdTasks()
    {
        return $this->hasMany(Task::class, 'created_by');
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->useDisk('public');
    }

    /**
     * Get avatar URL accessor.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getFirstMediaUrl('avatar') ?: null, // Return null jika tidak ada
        );
    }

    protected function isSuperAdmin(): Attribute
    {
        return Attribute::get(function () {
            return $this->roles()->where('name', 'like', 'super-admin-%')->exists();
        });
    }

    /**
     * Relationships
     */
    public function tasks()
    {
        return $this->belongsToMany(Task::class, 'task_users')
            ->withTimestamps()
            ->withPivot(['owned_id', 'created_by', 'updated_by', 'deleted_by'])
            ->using(TaskUser::class);
    }

    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'notification_users')
            ->withPivot('is_read')
            ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'task_users')
            ->withTrashed();
    }

    public function reactedComments(): HasMany
    {
        return $this->hasMany(CommentReaction::class, 'user_id');
    }

    public function projectMembers(): HasMany
    {
        return $this->hasMany(ProjectMember::class, 'user_id');
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withPivot('owned_id')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    /**
     * Append avatar_url to array/JSON serialization.
     *
     * @var array<int, string>
     */
    protected $appends = ['avatar_url'];
}
