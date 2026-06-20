import type { LazyTaskFormProps } from '@/pages/project-lazy';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';

const { postMock } = vi.hoisted(() => ({ postMock: vi.fn() }));

vi.mock('axios', () => ({
    default: { post: postMock, isAxiosError: (e: unknown): boolean => !!(e as { isAxiosError?: boolean })?.isAxiosError },
}));
vi.mock('primevue/usetoast', () => ({ useToast: () => ({ add: vi.fn() }) }));
vi.mock('@/composables/useProjectPermissions', () => ({
    useProjectPermissions: () => ({ canUpdateTaskField: () => true, canUpdateTaskStatus: () => true }),
}));
vi.mock('@inertiajs/vue3', () => ({
    usePage: () => ({ props: { auth: { user: null } } }),
    useForm: (initial: Record<string, unknown>) => {
        const form: Record<string, unknown> = {
            ...initial,
            errors: {} as Record<string, string>,
            transform(fn: (d: Record<string, unknown>) => Record<string, unknown>) {
                form.__transform = fn;
                return form;
            },
            reset: vi.fn(),
            clearErrors: vi.fn(() => {
                form.errors = {};
            }),
            setError: vi.fn((key: string, message: string) => {
                (form.errors as Record<string, string>)[key] = message;
            }),
        };
        return form;
    },
}));

import { useTaskForm } from './useTaskForm';

const baseProps = (): LazyTaskFormProps => ({
    parentId: null,
    projectId: 'PROJ',
    task: null,
    tasks: [],
    taskTypes: [{ id: 'T1', name: 'Bug', severity: 'danger' }],
    taskStatuses: [{ id: 'S1', name: 'In Progress', severity: 'info', score: 50 }],
    taskPriorities: [{ id: 'P1', name: 'High', severity: 'warning' }],
    taskCategories: [{ id: 'C1', name: 'Task' }],
    tags: [],
    members: [],
});

beforeEach(() => {
    postMock.mockReset();
    (globalThis as unknown as { route: unknown }).route = vi.fn(() => '/lazy-store');
});

afterEach(() => {
    vi.restoreAllMocks();
});

describe('useTaskForm.submit (create)', () => {
    it('posts to the lazy-store route and emits a saved payload built from the returned id', async () => {
        postMock.mockResolvedValue({ data: { success: true, id: 'NEWID' } });
        const emit = vi.fn();
        const props = baseProps();
        const { form, submit } = useTaskForm(props, emit);
        form.title = 'Created';
        form.status_id = 'S1';
        form.priority_id = 'P1';
        form.type_id = 'T1';
        form.task_category_id = 'C1';

        await submit();

        expect((globalThis as unknown as { route: ReturnType<typeof vi.fn> }).route).toHaveBeenCalledWith(
            'project.tasks.lazy-store',
            { projectEncoded: 'PROJ' },
        );
        expect(postMock).toHaveBeenCalledTimes(1);
        const savedCall = emit.mock.calls.find((c) => c[0] === 'saved');
        expect(savedCall).toBeTruthy();
        const payload = savedCall![1];
        expect(payload.mode).toBe('create');
        expect(payload.id).toBe('NEWID');
        expect(payload.title).toBe('Created');
        expect(payload.status?.id).toBe('S1');
        expect(payload.status?.score).toBe(50);
    });

    it('maps a 422 response into form errors and does not emit saved', async () => {
        postMock.mockRejectedValue({ isAxiosError: true, response: { status: 422, data: { errors: { title: ['Required'] } } } });
        const emit = vi.fn();
        const { form, submit } = useTaskForm(baseProps(), emit);

        await submit();

        expect((form.errors as Record<string, string>).title).toBe('Required');
        expect(emit.mock.calls.find((c) => c[0] === 'saved')).toBeFalsy();
    });
});
