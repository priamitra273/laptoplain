export function createTaskReportVisitOptions(
  setLoading: (loading: boolean) => void,
) {
  return {
    preserveState: true,
    preserveScroll: true,
    replace: true,
    only: ['tasks', 'filters'],
    showProgress: false,
    onStart: () => setLoading(true),
    onFinish: () => setLoading(false),
  };
}