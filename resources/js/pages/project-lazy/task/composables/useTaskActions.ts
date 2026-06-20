import { router } from '@inertiajs/vue3';
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
            accept: () => {
                ids.forEach((id) => {
                    router.delete(route('project.tasks.destroy', { projectEncoded: projectId, taskEncoded: id }), {
                        preserveScroll: true,
                    });
                });
                onCleared();
                toast.add({ severity: 'success', summary: 'Success', detail: `${ids.length} tasks deleted successfully`, life: 3000 });
            },
        });
    };

    return { deleteLoading, remove, removeSelected };
};
