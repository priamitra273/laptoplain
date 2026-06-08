type ConfigData = App.Data.ProjectRole.ConfigData;

export function useProjectPermissions(config: ConfigData | null) {
    const canAction = (resource: string, action: string): boolean => {
        if (!config) return false;
        const allowed = (config as Record<string, string[]>)[resource];
        return Array.isArray(allowed) && allowed.includes(action);
    };

    const canUpdateTaskStatus = (statusId: string): boolean => {
        if (!config) return false;
        const allowed = config.allow_task_status;
        return allowed.length === 0 || allowed.includes(statusId);
    };

    const canUpdateTaskField = (field: string): boolean => {
        if (!config) return false;
        const allowed = config.allow_update_task_fields;
        return allowed.length === 0 || allowed.includes(field as App.Enums.TaskField);
    };

    return { canAction, canUpdateTaskStatus, canUpdateTaskField };
}
