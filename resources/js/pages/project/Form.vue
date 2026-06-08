<script setup lang="ts">
// @ts-ignore
import { EmojiIndex, Picker } from 'emoji-mart-vue-fast/src';

import Label from '@/components/ui/label/Label.vue';
import { useForm } from '@inertiajs/vue3';
import 'emoji-mart-vue-fast/css/emoji-mart.css';
import emojiData from 'emoji-mart-vue-fast/data/all.json';
import moment from 'moment';
import { useToast } from 'primevue/usetoast';
import { computed, ref, watch as vueWatch } from 'vue';
import { ProjectForm, ProjectFormProps, ProjectPriority, ProjectStatus } from '.';

const emojiIndex = new EmojiIndex(emojiData);

const props = defineProps<ProjectFormProps>();
const emits = defineEmits<{ (e: 'update:visible', value: boolean): void }>();
const toast = useToast();

const showEmojiPicker = ref(false);

const visible = computed<boolean>({
    get() {
        return props.visible;
    },
    set(value) {
        emits('update:visible', value);
    },
});

const form = useForm<ProjectForm>({
    title: '',
    start_date: null,
    due_date: null,
    description: '',
    emoji: '🗒️',
    status_id: null,
    priority_id: null,
    owner_id: null,
    owned_id: null,
});

const onEmojiSelect = (emoji: any) => {
    form.emoji = emoji.native || emoji.emoji;
    showEmojiPicker.value = false;
};

const toggleEmojiPicker = () => {
    showEmojiPicker.value = !showEmojiPicker.value;
};

const save = (): void => {
    const url = route('project.store');

    form.transform((data) => ({
        ...data,
        start_date: data.start_date ? moment(data.start_date).format('YYYY-MM-DD') : null,
        due_date: data.due_date ? moment(data.due_date).format('YYYY-MM-DD') : null,
    }));

    form.post(url, {
        preserveScroll: true,
        onSuccess: () => {
            visible.value = false;
            form.reset();
            form.clearErrors();
        },
        onError: () => {
            toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to save project', life: 3000 });
        },
    });
};

const show = (): void => {
    form.reset();
    form.clearErrors();
    showEmojiPicker.value = false;
};

const hide = (): void => {
    form.reset();
    form.clearErrors();
    showEmojiPicker.value = false;
};

for (const key in form.data()) {
    vueWatch(
        () => form[key],
        () => {
            if (form[key]) {
                delete form.errors[key];
            }
        },
    );
}

const getSelectValue = (id: string, options: ProjectStatus[] | ProjectPriority[]) => {
    return options.find((option) => option.id === id) || null;
};
</script>

<template>
    <Drawer
        v-model:visible="visible"
        class="!w-full md:!w-[40vw]"
        position="right"
        header="Create New Project"
        @show="show"
        @after-hide="hide"
        :blockScroll="true"
        :dismissable="true"
    >
        <div class="grid gap-8 md:grid-cols-2">
            <div class="col-span-2 flex flex-col gap-2">
                <Label for="title">Project Title</Label>
                <InputGroup>
                    <InputGroupAddon class="cursor-pointer" @click="toggleEmojiPicker">
                        <span class="text-xl">{{ form.emoji || '😀' }}</span>
                    </InputGroupAddon>
                    <InputText v-model="form.title" id="title" placeholder="Enter Project Title" fluid />
                </InputGroup>
                <small v-if="form.errors.title" class="mt-1 text-sm text-red-500">{{ form.errors.title }}</small>

                <!-- Emoji Picker Popup -->
                <div v-if="showEmojiPicker" class="relative z-50">
                    <div class="absolute left-0 top-0 shadow-lg">
                        <Picker :data="emojiIndex" @select="onEmojiSelect" set="native" :native="true" title="Pick an emoji" emoji="point_up" />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <Label for="start_date">Start Date</Label>
                <DatePicker v-model="form.start_date" input-id="start_date" show-icon fluid date-format="yy-mm-dd" />
                <small v-if="form.errors.start_date" class="mt-1 text-sm text-red-500">{{ form.errors.start_date }}</small>
            </div>

            <div class="flex flex-col gap-2">
                <Label for="due_date">Due Date</Label>
                <DatePicker
                    v-model="form.due_date"
                    input-id="due_date"
                    show-icon
                    fluid
                    date-format="yy-mm-dd"
                    :min-date="form.start_date ?? undefined"
                />
                <small v-if="form.errors.due_date" class="mt-1 text-sm text-red-500">{{ form.errors.due_date }}</small>
            </div>

            <div class="flex flex-col gap-2">
                <Label for="status_id">Status</Label>
                <Select
                    v-model="form.status_id"
                    :options="props.statuses"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select Status"
                    class="w-full"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            <Tag
                                :value="getSelectValue(slotProps.value, props.statuses)?.name"
                                :severity="getSelectValue(slotProps.value, props.statuses)?.severity"
                            />
                        </div>
                        <span v-else>
                            {{ slotProps.placeholder }}
                        </span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex">
                            <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" class="w-full" />
                        </div>
                    </template>
                </Select>
                <small v-if="form.errors.status_id" class="mt-1 text-sm text-red-500">{{ form.errors.status_id }}</small>
            </div>

            <div class="flex flex-col gap-2">
                <Label for="priority_id">Priority</Label>
                <Select
                    v-model="form.priority_id"
                    :options="props.priorities"
                    optionLabel="name"
                    optionValue="id"
                    placeholder="Select Priority"
                    class="w-full"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            <Tag
                                :value="getSelectValue(slotProps.value, props.priorities)?.name"
                                :severity="getSelectValue(slotProps.value, props.priorities)?.severity"
                            />
                        </div>
                        <span v-else>
                            {{ slotProps.placeholder }}
                        </span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex">
                            <Tag :value="slotProps.option.name" :severity="slotProps.option.severity" class="w-full" />
                        </div>
                    </template>
                </Select>
                <small v-if="form.errors.priority_id" class="mt-1 text-sm text-red-500">{{ form.errors.priority_id }}</small>
            </div>

            <div class="col-span-2 flex flex-col gap-2">
                <Label for="description">Description</Label>
                <Editor v-model="form.description" editorStyle="height: 200px">
                    <template #toolbar>
                        <span class="ql-formats">
                            <button v-tooltip.bottom="'Bold'" class="ql-bold"></button>
                            <button v-tooltip.bottom="'Italic'" class="ql-italic"></button>
                            <button v-tooltip.bottom="'Underline'" class="ql-underline"></button>
                        </span>
                    </template>
                </Editor>

                <small v-if="form.errors.description" class="text-red-500">{{ form.errors.description }}</small>
            </div>
        </div>

        <template #footer>
            <div class="flex justify-end gap-2">
                <Button label="Cancel" severity="secondary" @click="visible = false" />
                <Button label="Save" @click="save" class="w-20" :loading="form.processing" :disabled="form.processing" />
            </div>
        </template>
    </Drawer>
</template>
