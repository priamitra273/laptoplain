<?php

// @formatter:off
// phpcs:ignoreFile
/**
 * A helper file for your Eloquent Models
 * Copy the phpDocs from this file to the correct Model,
 * And remove them from this file, to prevent double declarations.
 *
 * @author Barry vd. Heuvel <barryvdh@gmail.com>
 */


namespace App\Models{
/**
 * @property int $id
 * @property string $commentable_type
 * @property int $commentable_id
 * @property int $user_id
 * @property string $body
 * @property array<array-key, mixed>|null $reaction
 * @property int|null $owned_id
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $parent_id
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $commentable
 * @property-read \App\Models\User|null $creator
 * @property-read string|null $current_user_reaction
 * @property-read \App\Models\User|null $deleter
 * @property-read \App\Models\User|null $owner
 * @property-read Comment|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CommentReaction> $reaction_group_count
 * @property-read int|null $reaction_group_count_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CommentReaction> $reactions
 * @property-read int|null $reactions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Comment> $replies
 * @property-read int|null $replies_count
 * @property-read \App\Models\User|null $updater
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereCommentableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereCommentableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereOwnedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereReaction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Comment withoutTrashed()
 */
	class Comment extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $comment_id
 * @property int|null $user_id
 * @property string $reaction
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Comment|null $comment
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentReaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentReaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentReaction query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentReaction whereCommentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentReaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentReaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentReaction whereReaction($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentReaction whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CommentReaction whereUserId($value)
 */
	class CommentReaction extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $uuid
 * @property int|null $parent_id
 * @property string $label
 * @property string $icon
 * @property string|null $route_name
 * @property int $sequence_number
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Menu> $children
 * @property-read int|null $children_count
 * @property-read Menu|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereParentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereRouteName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereSequenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Menu withoutTrashed()
 */
	class Menu extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $name
 * @property string|null $severity
 * @property int|null $owned_id
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $created_by_user
 * @property-read \App\Models\User|null $deleted_by_user
 * @property-read \App\Models\User|null $owned
 * @property-read \App\Models\User|null $updated_by_user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectPriority newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectPriority newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectPriority onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectPriority query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectPriority whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectPriority whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectPriority whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectPriority whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectPriority whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectPriority whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectPriority whereOwnedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectPriority whereSeverity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectPriority whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectPriority whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectPriority withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectPriority withoutTrashed()
 */
	class MsProjectPriority extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $name
 * @property int|null $owned_id
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property array<array-key, mixed>|null $config
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User|null $created_by_user
 * @property-read \App\Models\User|null $deleted_by_user
 * @property-read \App\Models\User|null $owned
 * @property-read \App\Models\User|null $updated_by_user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectRole newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectRole newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectRole onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectRole query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectRole whereConfig($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectRole whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectRole whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectRole whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectRole whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectRole whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectRole whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectRole whereOwnedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectRole whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectRole whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectRole withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectRole withoutTrashed()
 */
	class MsProjectRole extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string|null $name
 * @property string|null $severity
 * @property int|null $owned_id
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User|null $created_by_user
 * @property-read \App\Models\User|null $deleted_by_user
 * @property-read \App\Models\User|null $owned
 * @property-read \App\Models\User|null $updated_by_user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectStatus onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectStatus query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectStatus whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectStatus whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectStatus whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectStatus whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectStatus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectStatus whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectStatus whereOwnedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectStatus whereSeverity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectStatus whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectStatus whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectStatus withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsProjectStatus withoutTrashed()
 */
	class MsProjectStatus extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property int $severity
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectSprint> $sprints
 * @property-read int|null $sprints_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsSprintStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsSprintStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsSprintStatus query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsSprintStatus whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsSprintStatus whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsSprintStatus whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsSprintStatus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsSprintStatus whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsSprintStatus whereSeverity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsSprintStatus whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsSprintStatus whereUpdatedBy($value)
 */
	class MsSprintStatus extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $severity
 * @property int|null $owned_id
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User|null $created_by_user
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User|null $deleted_by_user
 * @property-read \App\Models\User|null $deleter
 * @property-read \App\Models\User|null $owner
 * @property-read \App\Models\User|null $updated_by_user
 * @property-read \App\Models\User|null $updater
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskPriority newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskPriority newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskPriority onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskPriority query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskPriority whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskPriority whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskPriority whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskPriority whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskPriority whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskPriority whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskPriority whereOwnedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskPriority whereSeverity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskPriority whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskPriority whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskPriority withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskPriority withoutTrashed()
 */
	class MsTaskPriority extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $severity
 * @property int|null $owned_id
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int $score
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User|null $created_by_user
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User|null $deleted_by_user
 * @property-read \App\Models\User|null $deleter
 * @property-read \App\Models\User|null $owner
 * @property-read \App\Models\User|null $updated_by_user
 * @property-read \App\Models\User|null $updater
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus whereOwnedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus whereScore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus whereSeverity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskStatus withoutTrashed()
 */
	class MsTaskStatus extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $severity
 * @property int|null $owned_id
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User|null $created_by_user
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User|null $deleted_by_user
 * @property-read \App\Models\User|null $deleter
 * @property-read \App\Models\User|null $owner
 * @property-read \App\Models\User|null $updated_by_user
 * @property-read \App\Models\User|null $updater
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskType onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskType query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskType whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskType whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskType whereOwnedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskType whereSeverity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskType whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskType withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|MsTaskType withoutTrashed()
 */
	class MsTaskType extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $task_id
 * @property int|null $task_status_id
 * @property int|null $task_type_id
 * @property string $message
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\MsTaskStatus|null $status
 * @property-read \App\Models\Task|null $task
 * @property-read \App\Models\MsTaskType|null $type
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereTaskStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereTaskTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Notification whereUpdatedAt($value)
 */
	class Notification extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $notification_id
 * @property int $user_id
 * @property bool $is_read
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Notification $notification
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUser whereIsRead($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUser whereNotificationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationUser whereUserId($value)
 */
	class NotificationUser extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $status_id
 * @property int|null $priority_id
 * @property int|null $owner_id
 * @property int|null $owned_id
 * @property string|null $emoji
 * @property string|null $title
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property float $progress
 * @property int|null $sequence_number
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property string|null $project_no
 * @property string $code
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $allTasks
 * @property-read int|null $all_tasks_count
 * @property-read \App\Models\User|null $created_by_user
 * @property-read \App\Models\User|null $deleted_by_user
 * @property-read string|null $owned_name
 * @property-read string|null $owner_name
 * @property-read string|null $priority_name
 * @property-read string|null $status_name
 * @property-read \App\Models\User|null $owned
 * @property-read \App\Models\User|null $owner
 * @property-read \App\Models\MsProjectPriority|null $priority
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectMember> $projectMembers
 * @property-read int|null $project_members_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectSprint> $sprints
 * @property-read int|null $sprints_count
 * @property-read \App\Models\MsProjectStatus|null $status
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read \App\Models\User|null $updated_by_user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project visibleFor(\App\Models\User $user)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereEmoji($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereOwnedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project wherePriorityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereProgress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereProjectNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereSequenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withoutTrashed()
 */
	class Project extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $project_id
 * @property int|null $user_id
 * @property int|null $project_role_id
 * @property int|null $owned_id
 * @property bool $is_active
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User|null $created_by_user
 * @property-read \App\Models\User|null $deleted_by_user
 * @property-read \App\Models\User|null $owned
 * @property-read \App\Models\Project|null $project
 * @property-read \App\Models\MsProjectRole|null $role
 * @property-read \App\Models\User|null $updated_by_user
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember whereOwnedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember whereProjectRoleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectMember withoutTrashed()
 */
	class ProjectMember extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $project_id
 * @property int|null $sprint_status_id
 * @property string $name
 * @property string|null $goal
 * @property string|null $duration
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property int $order
 * @property string|null $retrospective
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User|null $created_by_user
 * @property-read \App\Models\User|null $deleted_by_user
 * @property-read \App\Models\Project|null $project
 * @property-read \App\Models\MsSprintStatus|null $status
 * @property-read \App\Models\SprintTask|null $pivot
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read \App\Models\User|null $updated_by_user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint whereGoal($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint whereOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint whereRetrospective($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint whereSprintStatusId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProjectSprint withoutTrashed()
 */
	class ProjectSprint extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string $guard_name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property string $label
 * @property int $team_id
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User|null $created_by_user
 * @property-read \App\Models\User|null $deleted_by_user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \App\Models\Team|null $team
 * @property-read \App\Models\User|null $updated_by_user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereGuardName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereTeamId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role withoutPermission($permissions)
 */
	class Role extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $sprint_id
 * @property int $task_id
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $created_by_user
 * @property-read \App\Models\User|null $deleted_by_user
 * @property-read \App\Models\User|null $updated_by_user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SprintTask newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SprintTask newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SprintTask query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SprintTask whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SprintTask whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SprintTask whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SprintTask whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SprintTask whereSprintId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SprintTask whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SprintTask whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|SprintTask whereUpdatedBy($value)
 */
	class SprintTask extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $severity
 * @property int|null $owned_id
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User|null $deleter
 * @property-read \App\Models\User|null $owner
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read \App\Models\User|null $updater
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereOwnedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereSeverity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag withoutTrashed()
 */
	class Tag extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $model_type
 * @property int $model_id
 * @property int $tag_id
 * @property int|null $owned_id
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Model|\Eloquent $model
 * @property-read \App\Models\User|null $owner
 * @property-read \App\Models\Tag|null $tag
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereModelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereOwnedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Taggable withoutTrashed()
 */
	class Taggable extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int|null $owned_id
 * @property int|null $parent_id
 * @property int|null $status_id
 * @property int|null $priority_id
 * @property int|null $type_id
 * @property string|null $start_date
 * @property string|null $due_date
 * @property string|null $emoji
 * @property string $title
 * @property string|null $description
 * @property float $progress
 * @property int|null $sequence_number
 * @property bool $is_archived
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property int|null $project_id
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property string|null $completed_at
 * @property int|null $task_category_id
 * @property int|null $story_points
 * @property int|null $epic_id
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\TaskCategory|null $category
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $children
 * @property-read int|null $children_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Comment> $comments
 * @property-read int|null $comments_count
 * @property-read \App\Models\User|null $created_by_user
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User|null $deleted_by_user
 * @property-read \App\Models\User|null $deleter
 * @property-read mixed $sub_task
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \App\Models\User|null $owner
 * @property-read \App\Models\Task|null $parent
 * @property-read \App\Models\MsTaskPriority|null $priority
 * @property-read \App\Models\Project|null $project
 * @property-read \App\Models\TaskUser|\App\Models\SprintTask|null $pivot
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ProjectSprint> $sprints
 * @property-read int|null $sprints_count
 * @property-read \App\Models\MsTaskStatus|null $status
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tag> $tags
 * @property-read int|null $tags_count
 * @property-read \App\Models\MsTaskType|null $type
 * @property-read \App\Models\User|null $updated_by_user
 * @property-read \App\Models\User|null $updater
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $usersWithTrashed
 * @property-read int|null $users_with_trashed_count
 * @property-read int $depth
 * @property-read string $path
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $ancestors The model's recursive parents.
 * @property-read int|null $ancestors_count
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $ancestorsAndSelf The model's recursive parents and itself.
 * @property-read int|null $ancestors_and_self_count
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $bloodline The model's ancestors, descendants and itself.
 * @property-read int|null $bloodline_count
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $childrenAndSelf The model's direct children and itself.
 * @property-read int|null $children_and_self_count
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $descendants The model's recursive children.
 * @property-read int|null $descendants_count
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $descendantsAndSelf The model's recursive children and itself.
 * @property-read int|null $descendants_and_self_count
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $parentAndSelf The model's direct parent and itself.
 * @property-read int|null $parent_and_self_count
 * @property-read \App\Models\Task|null $rootAncestor The model's topmost parent.
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $siblings The parent's other children.
 * @property-read int|null $siblings_count
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $siblingsAndSelf All the parent's children.
 * @property-read int|null $siblings_and_self_count
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, static> all($columns = ['*'])
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task backlog()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task breadthFirst()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task depthFirst()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task doesntHaveChildren()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task epics()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, static> get($columns = ['*'])
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task getExpressionGrammar()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task hasChildren()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task hasParent()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task isLeaf()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task isRoot()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task issues()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task newModelQuery()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task onlyTrashed()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task query()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task tree($maxDepth = null)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task treeOf(\Illuminate\Database\Eloquent\Model|callable $constraint, $maxDepth = null)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereCompletedAt($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereCreatedAt($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereCreatedBy($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereDeletedAt($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereDeletedBy($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereDepth($operator, $value = null)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereDescription($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereDueDate($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereEmoji($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereEpicId($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereId($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereIsArchived($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereOwnedId($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereParentId($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task wherePriorityId($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereProgress($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereProjectId($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereSequenceNumber($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereStartDate($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereStatusId($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereStoryPoints($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereTaskCategoryId($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereTitle($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereTypeId($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereUpdatedAt($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task whereUpdatedBy($value)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task withGlobalScopes(array $scopes)
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task withRecursive()
 * @method static \Staudenmeir\LaravelAdjacencyList\Eloquent\Builder<static>|Task withRelationshipExpression($direction, callable $constraint, $initialDepth, $from = null, $maxDepth = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task withoutTrashed()
 */
	class Task extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $name
 * @property string|null $icon
 * @property string|null $severity
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $created_by_user
 * @property-read \App\Models\User|null $deleted_by_user
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read \App\Models\User|null $updated_by_user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereIcon($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereSeverity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskCategory withoutTrashed()
 */
	class TaskCategory extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property int $task_id
 * @property int $user_id
 * @property int|null $owned_id
 * @property string|null $created_by
 * @property string|null $updated_by
 * @property string|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User|null $deleter
 * @property-read \App\Models\User|null $owner
 * @property-read \App\Models\Task|null $task
 * @property-read \App\Models\User|null $updater
 * @property-read \App\Models\User|null $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskUser onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskUser whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskUser whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskUser whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskUser whereOwnedId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskUser whereTaskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskUser whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskUser whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskUser whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskUser withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TaskUser withoutTrashed()
 */
	class TaskUser extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\User|null $created_by_user
 * @property-read \App\Models\User|null $deleted_by_user
 * @property-read \App\Models\User|null $updated_by_user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team filterByUserRole()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Team withoutTrashed()
 */
	class Team extends \Eloquent {}
}

namespace App\Models{
/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property string $email
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property bool $is_active
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Activitylog\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read mixed $avatar_url
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $createdTasks
 * @property-read int|null $created_tasks_count
 * @property-read User|null $created_by_user
 * @property-read User|null $deleted_by_user
 * @property-read mixed $is_super_admin
 * @property-read \Spatie\MediaLibrary\MediaCollections\Models\Collections\MediaCollection<int, \Spatie\MediaLibrary\MediaCollections\Models\Media> $media
 * @property-read int|null $media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Notification> $notifications
 * @property-read int|null $notifications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \Spatie\Permission\Models\Permission> $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CommentReaction> $reactedComments
 * @property-read int|null $reacted_comments_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @property-read \App\Models\TaskUser|null $pivot
 * @property-read \Staudenmeir\LaravelAdjacencyList\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read User|null $updated_by_user
 * @property-read \Illuminate\Database\Eloquent\Collection<int, User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User permission($permissions, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User role($roles, $guard = null, $without = false)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutPermission($permissions)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutRole($roles, $guard = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User withoutTrashed()
 */
	class User extends \Eloquent implements \Spatie\MediaLibrary\HasMedia {}
}

