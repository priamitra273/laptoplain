import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useTaskActions } from './useTaskActions';

const { deleteMock, reloadMock, toastAddMock } = vi.hoisted(() => ({
    deleteMock: vi.fn(),
    reloadMock: vi.fn(),
    toastAddMock: vi.fn(),
}));

let acceptFn: (() => unknown) | undefined;

vi.mock('axios', () => ({ default: { delete: deleteMock } }));
vi.mock('@inertiajs/vue3', () => ({ router: { reload: reloadMock } }));
vi.mock('primevue/usetoast', () => ({ useToast: () => ({ add: toastAddMock }) }));
vi.mock('primevue/useconfirm', () => ({
    useConfirm: () => ({
        require: (opts: { accept: () => unknown }) => {
            acceptFn = opts.accept;
        },
    }),
}));

beforeEach(() => {
    deleteMock.mockReset();
    reloadMock.mockReset();
    toastAddMock.mockReset();
    acceptFn = undefined;
    (globalThis as unknown as { route: unknown }).route = vi.fn(() => '/bulk-destroy');
});

describe('useTaskActions.removeSelected', () => {
    it('warns and skips the request when nothing is selected', () => {
        const { removeSelected } = useTaskActions('proj-1');

        removeSelected([], vi.fn());

        expect(acceptFn).toBeUndefined();
        expect(deleteMock).not.toHaveBeenCalled();
        expect(toastAddMock).toHaveBeenCalledWith(expect.objectContaining({ severity: 'warn' }));
    });

    it('sends one bulk request, reloads tasks and clears selection when all succeed', async () => {
        deleteMock.mockResolvedValue({ data: { deleted: 2, failed: 0, message: '2 task(s) deleted successfully.' } });
        const onCleared = vi.fn();
        const { removeSelected } = useTaskActions('proj-1');

        removeSelected(['a', 'b'], onCleared);
        await acceptFn?.();

        expect(deleteMock).toHaveBeenCalledTimes(1);
        expect(deleteMock).toHaveBeenCalledWith('/bulk-destroy', { data: { ids: ['a', 'b'] } });
        expect(onCleared).toHaveBeenCalledTimes(1);
        expect(reloadMock).toHaveBeenCalledWith({ only: ['tasks'] });
        expect(toastAddMock).toHaveBeenCalledWith(expect.objectContaining({ severity: 'success' }));
    });

    it('warns on a partial failure and still reloads tasks', async () => {
        deleteMock.mockResolvedValue({ data: { deleted: 1, failed: 1, message: '1 task(s) deleted, 1 could not be deleted.' } });
        const onCleared = vi.fn();
        const { removeSelected } = useTaskActions('proj-1');

        removeSelected(['a', 'b'], onCleared);
        await acceptFn?.();

        expect(onCleared).toHaveBeenCalledTimes(1);
        expect(reloadMock).toHaveBeenCalledOnce();
        expect(toastAddMock).toHaveBeenCalledWith(expect.objectContaining({ severity: 'warn' }));
    });

    it('reports an error and keeps the selection when nothing is deleted', async () => {
        deleteMock.mockResolvedValue({ data: { deleted: 0, failed: 2, message: '0 task(s) deleted, 2 could not be deleted.' } });
        const onCleared = vi.fn();
        const { removeSelected } = useTaskActions('proj-1');

        removeSelected(['a', 'b'], onCleared);
        await acceptFn?.();

        expect(onCleared).not.toHaveBeenCalled();
        expect(reloadMock).not.toHaveBeenCalled();
        expect(toastAddMock).toHaveBeenCalledWith(expect.objectContaining({ severity: 'error' }));
    });

    it('reports an error toast when the request throws', async () => {
        deleteMock.mockRejectedValue(new Error('network'));
        const onCleared = vi.fn();
        const { removeSelected } = useTaskActions('proj-1');

        removeSelected(['a'], onCleared);
        await acceptFn?.();

        expect(onCleared).not.toHaveBeenCalled();
        expect(toastAddMock).toHaveBeenCalledWith(expect.objectContaining({ severity: 'error' }));
    });
});
