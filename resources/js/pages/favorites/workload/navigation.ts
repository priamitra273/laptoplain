export function createWorkloadVisitOptions(partial = false) {
    return {
        preserveState: true,
        preserveScroll: true,
        preserveUrl: true,
        replace: true,
        ...(partial ? { only: ['users'], showProgress: false } : {}),
    };
}

export function replaceWorkloadBrowserUrl(history: Pick<History, 'replaceState'>, state: unknown, url: string): void {
    history.replaceState(state, '', url);
}
