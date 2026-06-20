import { router } from '@inertiajs/vue3';
import axios from 'axios';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { ref } from 'vue';

export const useTaskActions = (projectId: string) => {
    const confirm = useConfirm();
    const toast = useToast();
    const deleteLoading = ref(false);

    const remove = (t: { id: string; title: string }): void => {
        confirm.require({
            message: `Remove task ${t.title}? This action cannot be undone.`,
            header: 'Confirmation',
            icon: 'pi pi-exclamation-triangle',
            acceptLabel: 'Yes, remove',
            acceptClass: 'p-button-danger',
            rejectLabel: 'Cancel',
            accept: () => {
                deleteLoading.value = true;
                router.delete(
                    route('project.tasks.destroy', {
                        projectEncoded: projectId,
                        task: t.id,
                    }),
                    {
                        preserveScroll: true,
                        onError: () => {
                            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete task', life: 3000 });
                        },
                        onFinish: () => {
                            deleteLoading.value = false;
                        },
                    },
                );
            },
        });
    };

    const removeSelected = (ids: string[], onCleared: () => void): void => {
        if (!ids.length) {
            toast.add({ severity: 'warn', summary: 'Warning', detail: 'No tasks selected to delete.', life: 3000 });
            return;
        }
        confirm.require({
            message: `Delete ${ids.length} selected task(s)? This action cannot be undone.`,
            header: 'Confirmation',
            icon: 'pi pi-exclamation-triangle',
            acceptLabel: 'Yes, delete',
            acceptClass: 'p-button-danger',
            rejectLabel: 'Cancel',
            accept: async () => {
                deleteLoading.value = true;
                try {
                    // Single bulk request so feedback reflects the server's real outcome
                    // (incl. partial failures) instead of an optimistic per-row fire-and-forget.
                    const { data } = await axios.delete<{ deleted: number; failed: number; message: string }>(
                        route('project.tasks.bulk-destroy', { projectEncoded: projectId }),
                        { data: { ids } },
                    );

                    if (data.deleted > 0) {
                        onCleared();
                        router.reload({ only: ['tasks'] });
                    }

                    toast.add({
                        severity: data.failed > 0 ? (data.deleted > 0 ? 'warn' : 'error') : 'success',
                        summary: data.failed > 0 ? (data.deleted > 0 ? 'Partially deleted' : 'Delete failed') : 'Success',
                        detail: data.message,
                        life: 3000,
                    });
                } catch {
                    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete the selected tasks.', life: 3000 });
                } finally {
                    deleteLoading.value = false;
                }
            },
        });
    };

    return { deleteLoading, remove, removeSelected };
};
