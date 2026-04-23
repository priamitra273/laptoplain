declare namespace App {
    namespace Data {
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
        namespace Sprint {
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
                title: string;
                description: string | null;
                status: App.Data.Task.TaskStatusData | null;
                priority: App.Data.Task.TaskPriorityData | null;
                category: App.Data.Task.TaskCategoryData | null;
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
        export type Severity = 'success' | 'info' | 'warn' | 'danger';
        export type SiteStatus = 1 | 2 | 3 | 4 | 5;
        export type TaskNotificationType = 'created' | 'updated' | 'deleted' | 'mentioned';
        export type WorkloadStatus = 'Free' | 'Almost Done' | 'Ongoing' | 'Overloaded';
    }
}
