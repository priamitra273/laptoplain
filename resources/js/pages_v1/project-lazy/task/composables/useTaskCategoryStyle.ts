import { useSeverityColor } from '@/composables/useSeverityColor';
import type { TaskCategoryOption } from '@/pages/project-lazy';

export const taskCategoryIcon = (category?: TaskCategoryOption | null): string => {
    const byName: Record<string, string> = {
        Epic: 'pi pi-bolt',
        Issue: 'pi pi-exclamation-circle',
        Story: 'pi pi-book',
        Task: 'pi pi-check-square',
    };
    const name = category?.name ?? '';
    return category?.icon ?? byName[name] ?? 'pi pi-tag';
};

export const taskCategoryColorByName = (category?: TaskCategoryOption | null): string => {
    const byName: Record<string, string> = {
        Epic: '#7c3aed',
        Issue: '#dc2626',
        Story: '#16a34a',
        Task: '#3b82f6',
        Bug: '#dc2626',
    };
    const name = category?.name ?? '';
    return byName[name] ?? '#64748b';
};

export const useTaskCategoryStyle = () => {
    const { getSeverityColorLight } = useSeverityColor();

    const getCategoryIcon = (category?: TaskCategoryOption | null): string => taskCategoryIcon(category);

    const getCategoryColor = (category?: TaskCategoryOption | null): string => {
        const severity = category?.severity;
        if (severity) {
            return getSeverityColorLight(severity, 0.2);
        }
        return taskCategoryColorByName(category);
    };

    return { getCategoryIcon, getCategoryColor };
};
