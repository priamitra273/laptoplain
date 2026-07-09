<?php

use App\Models\MsProjectPriority;
use App\Models\MsProjectRole;
use App\Models\MsProjectStatus;
use App\Models\MsSprintStatus;
use App\Models\MsTaskPriority;
use App\Models\MsTaskStatus;
use App\Models\MsTaskType;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\ProjectSprint;
use App\Models\SprintTask;
use App\Models\Tag;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\TaskUser;
use App\Models\User;

return [

    'defaults' => [
        'limit' => 50,
        'max_limit' => 200,
        'allowed_operators' => ['=', '!=', '>', '>=', '<', '<=', 'like', 'in', 'not in', 'is null', 'is not null'],
        'allowed_aggregates' => ['count', 'sum', 'avg', 'min', 'max'],
        // Mirrors App\Models\Project::scopeVisibleFor().
        'super_admin_roles' => ['super-admin-admin', 'watcher-admin'],
    ],

    'models' => [

        'project' => [
            'table' => 'projects',
            'model' => Project::class,
            'scope' => 'project',
            'project_key' => ['type' => 'column', 'column' => 'id'],
            'soft_delete' => true,
            'columns' => ['id', 'project_no', 'code', 'status_id', 'priority_id', 'owner_id', 'owned_id', 'emoji', 'title', 'description', 'start_date', 'due_date', 'progress', 'sequence_number', 'created_at', 'updated_at'],
            'relations' => [
                'status' => 'ms_project_status',
                'priority' => 'ms_project_priority',
                'owner' => 'user',
                'owned' => 'user',
                'projectMembers' => 'project_member',
                'sprints' => 'project_sprint',
                'tasks' => 'task',
            ],
            'joinable' => ['ms_project_status', 'ms_project_priority', 'user', 'project_member', 'project_sprint', 'task'],
        ],

        'task' => [
            'table' => 'tasks',
            'model' => Task::class,
            'scope' => 'project',
            'project_key' => ['type' => 'column', 'column' => 'project_id'],
            'soft_delete' => true,
            'columns' => ['id', 'owned_id', 'parent_id', 'status_id', 'priority_id', 'type_id', 'project_id', 'task_category_id', 'created_by', 'updated_by', 'emoji', 'title', 'description', 'progress', 'story_points', 'sequence_number', 'is_archived', 'start_date', 'due_date', 'completed_at', 'created_at', 'updated_at'],
            'relations' => [
                'owner' => 'user',
                'parent' => 'task',
                'children' => 'task',
                'status' => 'ms_task_status',
                'priority' => 'ms_task_priority',
                'type' => 'ms_task_type',
                'category' => 'task_category',
                'project' => 'project',
                'creator' => 'user',
                'users' => 'user',
                'sprints' => 'project_sprint',
                'tags' => 'tag',
            ],
            'joinable' => ['ms_task_status', 'ms_task_priority', 'ms_task_type', 'task_category', 'project', 'user', 'project_sprint', 'sprint_task'],
        ],

        'project_sprint' => [
            'table' => 'project_sprints',
            'model' => ProjectSprint::class,
            'scope' => 'project',
            'project_key' => ['type' => 'column', 'column' => 'project_id'],
            'soft_delete' => true,
            'columns' => ['id', 'project_id', 'sprint_status_id', 'name', 'goal', 'duration', 'start_date', 'end_date', 'order', 'retrospective', 'created_at', 'updated_at'],
            'relations' => [
                'project' => 'project',
                'status' => 'ms_sprint_status',
                'tasks' => 'task',
            ],
            'joinable' => ['project', 'ms_sprint_status', 'sprint_task', 'task'],
        ],

        'sprint_task' => [
            'table' => 'sprint_task',
            'model' => SprintTask::class,
            'scope' => 'project',
            'project_key' => ['type' => 'subquery', 'column' => 'sprint_id', 'via_table' => 'project_sprints', 'via_select' => 'id', 'via_where' => 'project_id', 'via_soft_delete' => true],
            'soft_delete' => false,
            'columns' => ['id', 'sprint_id', 'task_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['project_sprint', 'task'],
        ],

        'project_member' => [
            'table' => 'project_members',
            'model' => ProjectMember::class,
            'scope' => 'project',
            'project_key' => ['type' => 'column', 'column' => 'project_id'],
            'soft_delete' => true,
            'columns' => ['id', 'project_id', 'user_id', 'project_role_id', 'owned_id', 'is_active', 'created_at', 'updated_at'],
            'relations' => [
                'project' => 'project',
                'user' => 'user',
                'role' => 'ms_project_role',
                'owned' => 'user',
            ],
            'joinable' => ['project', 'user', 'ms_project_role'],
        ],

        'task_user' => [
            'table' => 'task_users',
            'model' => TaskUser::class,
            'scope' => 'project',
            'project_key' => ['type' => 'subquery', 'column' => 'task_id', 'via_table' => 'tasks', 'via_select' => 'id', 'via_where' => 'project_id', 'via_soft_delete' => true],
            'soft_delete' => true,
            'columns' => ['id', 'task_id', 'user_id', 'owned_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['task', 'user'],
        ],

        'user' => [
            'table' => 'users',
            'model' => User::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            // Deliberately excludes password, remember_token, uuid.
            'columns' => ['id', 'name', 'email', 'is_active', 'created_at', 'updated_at'],
            'relations' => [
                'projects' => 'project',
                'projectMembers' => 'project_member',
                'createdTasks' => 'task',
            ],
            'joinable' => ['project_member', 'task_user'],
        ],

        'ms_project_status' => [
            'table' => 'ms_project_statuses',
            'model' => MsProjectStatus::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            'columns' => ['id', 'name', 'severity', 'owned_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['project'],
        ],

        'ms_project_priority' => [
            'table' => 'ms_project_priority',
            'model' => MsProjectPriority::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            'columns' => ['id', 'name', 'severity', 'owned_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['project'],
        ],

        'ms_task_status' => [
            'table' => 'ms_task_statuses',
            'model' => MsTaskStatus::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            'columns' => ['id', 'name', 'severity', 'score', 'owned_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['task'],
        ],

        'ms_task_priority' => [
            'table' => 'ms_task_priorities',
            'model' => MsTaskPriority::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            'columns' => ['id', 'name', 'severity', 'owned_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['task'],
        ],

        'ms_task_type' => [
            'table' => 'ms_task_types',
            'model' => MsTaskType::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            'columns' => ['id', 'name', 'severity', 'owned_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['task'],
        ],

        'task_category' => [
            'table' => 'task_categories',
            'model' => TaskCategory::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            'columns' => ['id', 'name', 'icon', 'severity', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['task'],
        ],

        'tag' => [
            'table' => 'tags',
            'model' => Tag::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            'columns' => ['id', 'name', 'severity', 'owned_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => [],
        ],

        'ms_sprint_status' => [
            'table' => 'ms_sprint_statuses',
            'model' => MsSprintStatus::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => false,
            'columns' => ['id', 'name', 'severity', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['project_sprint'],
        ],

        'ms_project_role' => [
            'table' => 'ms_project_roles',
            'model' => MsProjectRole::class,
            'scope' => 'global',
            'project_key' => null,
            'soft_delete' => true,
            'columns' => ['id', 'name', 'owned_id', 'created_at', 'updated_at'],
            'relations' => [],
            'joinable' => ['project_member'],
        ],

    ],
];
