<script setup lang="ts">
import { Team } from '@/types';
import { InertiaForm, useForm } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { computed } from 'vue';

interface Props {
    value?: Team
}

interface TeamForm {
    _method: string;
    string?: string;
    [key: string]: any;
}

const props = defineProps<Props>()
const emits = defineEmits<{ close: [boolean] }>()

const title = computed(() => props.value ? 'Edit Team' : 'Add Team')

const toast = useToast()

const form: InertiaForm<TeamForm> = useForm({
    _method: 'POST',
    name: ''
});

const open = () => {
    form.name = props.value?.name || ''
}

const save = (): void => {
    const url = props.value?.uuid ? route('team.update', props.value.uuid) : route('team.store');

    form._method = props.value?.uuid ? 'PUT' : 'POST'

    form.post(url, {
        preserveScroll: true,
        onSuccess() {
            toast.add({ title: 'Success', description: 'Successfully save data', color: 'success' })
            emits('close', true)
        }
    })
}
for (const key in form.data()) {
    watchDebounced(() => form[key], () => {
        delete form.errors[key]
    }, {
        debounce: 500,
        maxWait: 1000
    })
}
</script>

<template>
    <USlideover :title="title" :close="{ onClick: () => emits('close', false) }" @enter="open">
        <template #body>
            <div class="grid gap-8">
                <div class="flex flex-col gap-2">
                    <Label value="Team Name"></Label>
                    <UInput v-model="form.name" placeholder="Team Name" />
                    <InputError :message="form.errors.name" v-if="form.errors.name" />
                </div>
            </div>
        </template>

        <template #footer>
            <UButton label="Submit" :loading="form.processing" :disabled="form.processing" @click="save" />
        </template>
    </USlideover>
</template>