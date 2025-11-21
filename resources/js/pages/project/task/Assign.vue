<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Dropdown from 'primevue/dropdown';
import { computed } from 'vue';

interface Props {
    projectId: string;
    task: any;
    assignableUsers: { id: string; name: string }[];
}

const props = defineProps<Props>();
const emit = defineEmits(['close', 'saved']);

const form = useForm({
    user_id: null as string | null,
});

const userOptions = computed(() => props.assignableUsers);
const submit = () => {
    if (!props.task?.id) {
        console.error('Task.id tidak ada');
        return;
    }

    form.post(
        route('project.tasks.assign', {
            projectEncoded: props.projectId,
            taskEncoded: props.task.id,
        }),
        {
            preserveScroll: true,
            onSuccess: () => emit('saved'),
        },
    );
};
</script>

<template>
    <div class="flex flex-col gap-4 p-2">
        <Dropdown v-model="form.user_id" :options="userOptions" optionLabel="name" optionValue="id" placeholder="Select a user" class="w-full" />

        <div class="mt-4 flex justify-end gap-2">
            <Button label="Cancel" severity="secondary" @click="$emit('close')" />
            <Button label="Assign" :disabled="!form.user_id" @click="submit" />
        </div>
    </div>
</template>
