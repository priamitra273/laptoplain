<script setup lang="ts">
import Button from 'primevue/button';
import DatePicker from 'primevue/datepicker';
import Dialog from 'primevue/dialog';

interface Props {
    visible: boolean;
    dueDate: Date | null;
}

const props = defineProps<Props>();
const emit = defineEmits<{
    (e: 'update:visible', value: boolean): void;
    (e: 'update:dueDate', value: Date | Date[] | (Date | null)[] | null | undefined): void;
    (e: 'confirm'): void;
    (e: 'cancel'): void;
}>();

const closeDialog = () => emit('cancel');
</script>

<template>
    <Dialog
        :visible="props.visible"
        @update:visible="(val) => emit('update:visible', val)"
        modal
        :closable="false"
        :draggable="false"
        header="Set Due Date"
        class="w-full max-w-md"
    >
        <template #header>
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-50 dark:bg-primary-900/30">
                    <i class="pi pi-calendar-clock text-primary-600 dark:text-primary-300"></i>
                </div>
                <div>
                    <p class="text-base font-semibold text-surface-900 dark:text-surface-50">Set Due Date</p>
                    <p class="text-xs text-surface-500 dark:text-surface-400">Required to move task to In Progress</p>
                </div>
            </div>
        </template>

        <div class="flex flex-col gap-4 py-2">
            <p class="text-sm text-surface-600 dark:text-surface-300">
                This task doesn't have a due date yet. Please set a due date before marking it as
                <span class="font-semibold text-primary-600 dark:text-primary-400">In Progress</span>.
            </p>

            <div class="flex flex-col gap-1.5">
                <label class="text-xs font-medium text-surface-500 dark:text-surface-400">
                    Due date <span class="text-rose-600 dark:text-rose-400">*</span>
                </label>
                <DatePicker
                    :modelValue="props.dueDate"
                    @update:modelValue="(val) => $emit('update:dueDate', val)"
                    dateFormat="dd M yy"
                    class="w-full"
                    showIcon
                    placeholder="Select due date"
                    :minDate="new Date()"
                />
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2 pt-2">
                <Button label="Cancel" severity="secondary" text @click="closeDialog" />
                <Button label="Confirm & Save" icon="pi pi-check" :disabled="!props.dueDate" @click="emit('confirm')" />
            </div>
        </template>
    </Dialog>
</template>
