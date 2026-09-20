import { useConfirmDialog } from '@/composables/useConfirmDialog';
import { fetchJson, FetchJsonError } from '@/lib/utils';

interface BulkDestroyResult {
    success: boolean;
    deleted: number;
    failed: number;
    message: string;
}

/**
 * Konfirmasi + panggil endpoint bulk-destroy + toast — sebelumnya diimplementasikan dua kali
 * (Kanban dan List) dengan kata-kata konfirmasi berbeda dan pembacaan respons yang beda pula
 * (satu percaya `success`, satu mensyaratkan `deleted === 1`). Endpoint sebenarnya cuma
 * mengembalikan `success: deleted > 0`, jadi itu satu-satunya yang perlu dibaca.
 */
export function useTaskDelete(projectId: () => string) {
    const confirm = useConfirmDialog();
    const toast = useToast();

    /** `undefined` berarti dibatalkan lewat dialog konfirmasi — pemanggil tidak perlu memuat ulang data. */
    const deleteTask = async (task: { id: string; title: string }): Promise<boolean | undefined> => {
        const confirmed = await confirm({
            title: 'Delete task?',
            description: `Delete "${task.title}" and its subtasks? This cannot be undone.`,
        });

        if (!confirmed) {
            return undefined;
        }

        try {
            const result = await fetchJson<BulkDestroyResult>(
                route('project.tasks.bulk-destroy', { projectEncoded: projectId() }),
                'DELETE',
                { ids: [task.id] },
            );

            toast.add({
                title: result.success ? 'Success' : 'Failed',
                description: result.message,
                color: result.success ? 'success' : 'error',
            });

            return result.success;
        } catch (error) {
            toast.add({
                title: 'Failed',
                description: error instanceof FetchJsonError ? error.message : 'Could not delete the task.',
                color: 'error',
            });

            return false;
        }
    };

    return { deleteTask };
}
