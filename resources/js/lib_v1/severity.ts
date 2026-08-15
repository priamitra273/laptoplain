export type SeverityClasses = {
    colBg: string;
    colBorder: string;
    headerText: string;
    dot: string;
};

export const severityClasses: Record<string, SeverityClasses> = {
    primary: {
        colBg: 'bg-slate-50 dark:bg-slate-900/40',
        colBorder: 'border-slate-200 dark:border-slate-700/60',
        headerText: 'text-violet-600 dark:text-violet-400',
        dot: 'bg-violet-500',
    },
    secondary: {
        colBg: 'bg-slate-50 dark:bg-slate-900/40',
        colBorder: 'border-slate-200 dark:border-slate-700/60',
        headerText: 'text-slate-600 dark:text-slate-400',
        dot: 'bg-slate-400',
    },
    success: {
        colBg: 'bg-emerald-50/40 dark:bg-emerald-950/20',
        colBorder: 'border-emerald-200 dark:border-emerald-800/50',
        headerText: 'text-emerald-600 dark:text-emerald-400',
        dot: 'bg-emerald-500',
    },
    info: {
        colBg: 'bg-sky-50/40 dark:bg-sky-950/20',
        colBorder: 'border-sky-200 dark:border-sky-800/50',
        headerText: 'text-sky-600 dark:text-sky-400',
        dot: 'bg-sky-500',
    },
    warn: {
        colBg: 'bg-amber-50/40 dark:bg-amber-950/20',
        colBorder: 'border-amber-200 dark:border-amber-800/50',
        headerText: 'text-amber-600 dark:text-amber-400',
        dot: 'bg-amber-500',
    },
    warning: {
        colBg: 'bg-amber-50/40 dark:bg-amber-950/20',
        colBorder: 'border-amber-200 dark:border-amber-800/50',
        headerText: 'text-amber-600 dark:text-amber-400',
        dot: 'bg-amber-500',
    },
    danger: {
        colBg: 'bg-rose-50/40 dark:bg-rose-950/20',
        colBorder: 'border-rose-200 dark:border-rose-800/50',
        headerText: 'text-rose-600 dark:text-rose-400',
        dot: 'bg-rose-500',
    },
    contrast: {
        colBg: 'bg-gray-50 dark:bg-gray-900/40',
        colBorder: 'border-gray-200 dark:border-gray-700/60',
        headerText: 'text-gray-700 dark:text-gray-300',
        dot: 'bg-gray-600',
    },
    default: {
        colBg: 'bg-gray-50 dark:bg-gray-900/40',
        colBorder: 'border-gray-200 dark:border-gray-700/60',
        headerText: 'text-gray-600 dark:text-gray-400',
        dot: 'bg-gray-400',
    },
};
