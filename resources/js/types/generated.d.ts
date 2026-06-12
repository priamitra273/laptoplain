declare namespace App {
    namespace Data {
        export type MediaData = {
            uuid: string;
            file_name: string;
            size: number;
            mime_type: string;
            url: string;
            created_at: undefined;
            updated_at: undefined;
        };
        export type UserData = {
            id: number;
            name: string;
            email: string;
            avatar_url: string | null;
        };
        namespace Project {
            export type ProjectData = {
                id: number;
                project_no: string | null;
                title: string | null;
                description: string | null;
                emoji: string | null;
                start_date: string | null;
                due_date: string | null;
                progress: number;
                status_id: number | null;
                priority_id: number | null;
                owner_id: number | null;
                owned_id: number | null;
                sequence_number: number | null;
                created_at: string | null;
                updated_at: string | null;
                status: App.Data.Project.ProjectStatusData | null;
                priority: App.Data.Project.ProjectPriorityData | null;
                owner: App.Data.UserData | null;
                owned: App.Data.UserData | null;
            };
            export type ProjectMemberData = {
                id: number;
                is_active: boolean;
                user: App.Data.UserData;
                role: App.Data.Project.ProjectRoleData;
            };
            export type ProjectPriorityData = {
                id: number;
                name: string;
                severity: string | null;
            };
            export type ProjectRoleData = {
                id: number;
                name: string;
            };
            export type ProjectStatusData = {
                id: string;
                name: string;
                severity: string | null;
            };
        }
        namespace ProjectRole {
            export type ConfigData = {
                task: App.Enums.ProjectRolePermission[];
                sprint: App.Enums.ProjectRolePermission[];
                project_member: App.Enums.ProjectRolePermission[];
                allow_task_status: string[];
                allow_update_task_fields: App.Enums.TaskField[];
            };
        }
        namespace Sprint {
            export type BurndownChartData = {
                date: string;
                label: string;
                total_plan: number;
                total_actual: number;
            };
            export type SprintData = {
                id: string;
                project_id: string;
                name: string;
                goal: string | null;
                duration: string | null;
                start_date: string;
                end_date: string;
                order: number;
                retrospective: string | null;
                status: App.Data.Sprint.StatusData | null;
                created_at: string;
                updated_at: string;
            };
            export type SprintStatusReportData = {
                completed_tasks: App.Data.Task.TaskData[];
                incomplete_tasks: App.Data.Task.TaskData[];
            };
            export type StatusData = {
                id: string;
                name: string;
                severity: string;
            };
        }
        namespace Task {
            export type FilterOptionData = {
                id: string;
                name: string;
                severity: string | null;
                avatar_url: string | null;
            };
            export type ProjectTaskData = {
                id: string;
                owned_id: string | null;
                parent_id: string | null;
                status_id: string | null;
                priority_id: string | null;
                type_id: string | null;
                created_by: number | null;
                updated_by: number | null;
                deleted_by: number | null;
                emoji: string | null;
                title: string;
                description: string | null;
                start_date: string | null;
                due_date: string | null;
                progress: number;
                story_points: number | null;
                sequence_number: number | null;
                is_archived: boolean;
                created_at: string | null;
                updated_at: string | null;
                deleted_at: string | null;
                completed_at: string | null;
                is_overdue: boolean;
                project_id: string;
                status: App.Data.Task.TaskStatusData | null;
                priority: App.Data.Task.TaskPriorityData | null;
                type: App.Data.Task.TaskTypeData | null;
                category: App.Data.Task.TaskCategoryData | null;
                creator: App.Data.UserData | null;
                users: App.Data.UserData[];
                tags: App.Data.Task.TagData[];
                media: App.Data.MediaData[];
                sub_task: App.Data.Task.ProjectTaskData[];
                sub_task_recursive: App.Data.Task.ProjectTaskData[];
                is_assigned: boolean | null;
                is_created_by_me: boolean | null;
            };
            export type TagData = {
                id: number;
                name: string;
                severity: string | null;
            };
            export type TaskActivityData = {
                id: string;
                event: string;
                causer: App.Data.UserData | null;
                changed_fields: App.Data.Task.TaskActivityFieldData[];
                created_at: string;
            };
            export type TaskActivityFieldData = {
                field: string;
                old_value: string | null;
                new_value: string | null;
                has_value: boolean;
            };
            export type TaskCategoryData = {
                id: string;
                name: string;
                icon: string | null;
                severity: string | null;
            };
            export type TaskData = {
                id: string;
                key: string;
                title: string;
                description: string | null;
                status: App.Data.Task.TaskStatusData | null;
                priority: App.Data.Task.TaskPriorityData | null;
                category: App.Data.Task.TaskCategoryData | null;
                rootAncestor: App.Data.Task.TaskData | null;
                users: App.Data.UserData[] | null;
                progress: number | null;
                story_points: number | null;
            };
            export type TaskParentData = {
                id: string;
                key: string;
                title: string;
                category: App.Data.Task.TaskCategoryData | null;
            };
            export type TaskPriorityData = {
                id: number;
                name: string;
                severity: string | null;
            };
            export type TaskReportData = {
                id: string;
                title: string;
                description: string | null;
                summary: string;
                creator: Array<any> | null;
                status: Array<any> | null;
                priority: Array<any> | null;
                type: Array<any> | null;
                project: Array<any> | null;
                start_date: string | null;
                due_date: string | null;
                progress: number | null;
                created_at: string;
                updated_at: string;
            };
            export type TaskReportIndexData = {
                tasks: undefined | undefined;
                filters: Array<any>;
                filterOptions: Array<any>;
                project_statuses: Array<any>;
            };
            export type TaskStatusData = {
                id: number;
                name: string;
                severity: string | null;
                score: number | null;
            };
            export type TaskTypeData = {
                id: number;
                name: string;
                severity: string | null;
            };
        }
    }
    namespace Enums {
        export type ProjectRolePermission = 'create' | 'update' | 'delete';
        export type Severity = 'success' | 'info' | 'warn' | 'danger';
        export type SiteStatus = 1 | 2 | 3 | 4 | 5;
        export type TaskField =
            | 'title'
            | 'description'
            | 'status'
            | 'priority'
            | 'type'
            | 'category'
            | 'start_date'
            | 'end_date'
            | 'due_date'
            | 'tags'
            | 'assignee'
            | 'parent';
        export type TaskNotificationType = 'created' | 'updated' | 'deleted' | 'mentioned';
        export type TaskStatusEnum = 'To Do' | 'In Progress' | 'In Review' | 'Completed' | 'Blocked' | 'Finished';
        export type WorkloadStatus = 'Free' | 'Almost Done' | 'Ongoing' | 'Overloaded';
    }
}
