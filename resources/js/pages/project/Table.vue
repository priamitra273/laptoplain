<script setup lang="ts">
import DropdownButton from '@/components/DropdownButton.vue';
import Icon from '@/components/Icon.vue';
import { PrimeSeverity, Project } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode } from '@primevue/core/api';
import 'emoji-mart-vue-fast/css/emoji-mart.css';
import emojiData from 'emoji-mart-vue-fast/data/all.json';
import { EmojiIndex, Picker } from 'emoji-mart-vue-fast/src';
import moment from 'moment';
import { MenuItem } from 'primevue/menuitem';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { ref, watch } from 'vue';
import ProjectForm from './Form.vue';

const emojiIndex = new EmojiIndex(emojiData);

interface ProjectStatus {
    id: string;
    name: string;
    severity: PrimeSeverity;
}

interface ProjectPriority {
    id: string;
    name: string;
    severity: PrimeSeverity;
}

interface Props {
    projects?: Project[];
    statuses: ProjectStatus[];
    priorities: ProjectPriority[];
    progresses?: number;
    hasPermission?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    projects: () => [],
    statuses: () => [],
    priorities: () => [],
    progresses: () => 0,
});

const toast = useToast();
const confirm = useConfirm();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
});

const visibleForm = ref<boolean>(false);
const selected = ref<Project | undefined>(undefined);

const goToCreate = () => {
    selected.value = undefined;
    visibleForm.value = true;
};

const items: MenuItem[] = [
    {
        label: 'View Detail',
        command(event) {
            const data = event.item.data;
            router.visit(route('project.show', { encoded: data.id }));
        }
    },
];

if (props.hasPermission) {
    items.push({
        label: 'Delete',
        command(event) {
            confirmDelete(event.item.data);
        }
    });
}

const onCellEditComplete = ({ data, newValue, field }: { data: any; newValue: any; field: string }) => {
    if (data[field] === newValue) return;

    let payload: any = { ...data };
    if (field === 'start_date' || field === 'due_date') {
        payload[field] = moment(newValue).format('YYYY-MM-DD');
    } else {
        payload[field] = newValue;
    }

    payload.start_date = moment(payload.start_date).format('YYYY-MM-DD');
    payload.due_date = moment(payload.due_date).format('YYYY-MM-DD');

    router.put(route('project.update', data.encoded || data.id), payload, {
        preserveScroll: true,
        preserveState: true,
    });
};

const confirmDelete = (project: Project) => {
    confirm.require({
        message: `Are you sure you want to delete "${project.title}"?`,
        header: 'Confirm Deletion',
        icon: 'pi pi-exclamation-triangle',
        rejectLabel: 'Cancel',
        acceptLabel: 'Yes, Delete',
        acceptClass: 'p-button-danger',
        accept: () => {
            router.delete(route('project.destroy', { project: project.id }), {
                preserveScroll: true,
                onError: () => {
                    toast.add({ severity: 'error', summary: 'Error', detail: 'Failed to delete project', life: 3000 });
                },
            });
        },
    });
};

const truncateHtmlPreserve = (html: string, maxLength = 20) => {
    if (!html) return '';

    const div = document.createElement('div');
    div.innerHTML = html;

    let totalLength = 0;

    const truncateNode = (node: Node): Node | null => {
        if (totalLength >= maxLength) return null;

        if (node.nodeType === Node.TEXT_NODE) {
            const text = node.nodeValue || '';
            if (totalLength + text.length <= maxLength) {
                totalLength += text.length;
                return document.createTextNode(text);
            } else {
                const truncated = text.substring(0, maxLength - totalLength) + '...';
                totalLength = maxLength;
                return document.createTextNode(truncated);
            }
        }

        if (node.nodeType === Node.ELEMENT_NODE) {
            const clone = node.cloneNode(false);
            for (const child of Array.from(node.childNodes)) {
                const truncatedChild = truncateNode(child);
                if (truncatedChild) clone.appendChild(truncatedChild);
                if (totalLength >= maxLength) break;
            }
            return clone;
        }

        return null;
    };

    const result = truncateNode(div) as HTMLDivElement;
    return result ? result.innerHTML : '';
};

const onEmojiSelect = (emoji: any, data: any) => {
    const emojiNative = emoji.native || emoji.emoji;

    onCellEditComplete({
        data: data,
        newValue: emojiNative,
        field: 'emoji',
    });
};

watch(visibleForm, (val) => {
    if (!val) selected.value = undefined;
});
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-center justify-between gap-2">
            <IconField>
                <InputText v-model="filters.global.value" placeholder="Search Project..." />
                <InputIcon>
                    <Icon name="search" />
                </InputIcon>
            </IconField>

            <Button v-if="props.hasPermission" icon="pi pi-plus" label="Add Project" @click="goToCreate" />
        </div>

        <div class="card overflow-hidden">
            <DataTable
                :value="projects"
                v-model:filters="filters"
                data-key="id"
                editMode="cell"
                @cell-edit-complete="onCellEditComplete"
                paginator
                :rows="10"
                :rowsPerPageOptions="[10, 25, 50]"
                :globalFilterFields="['title', 'description']"
                striped-rows
                row-hover
                :closeOnEscape="false"
            >
                <Column header="No" class="w-12 text-center">
                    <template #body="{ index }">{{ index + 1 }}</template>
                </Column>

                <Column field="emoji" header="Emoji" class="w-20">
                    <template #body="{ data }">
                        <span class="text-2xl">{{ data.emoji || '😀' }}</span>
                    </template>

                    <template v-if="props.hasPermission" #editor="{ data }">
                        <div @click.stop class="emoji-picker-wrapper">
                            <Picker
                                :data="emojiIndex"
                                @select="(emoji: any) => onEmojiSelect(emoji, data)"
                                set="native"
                                :native="true"
                                title="Pick an emoji"
                                emoji="point_up"
                            />
                        </div>
                    </template>
                </Column>

                <Column field="title" header="Title" sortable :sortOrder="-1">
                    <template v-if="props.hasPermission" #editor="{ data, field }">
                        <InputText v-model="data[field]" class="w-full" />
                    </template>
                </Column>

                <Column field="description" header="Description">
                    <template #body="{ data }">
                        <div class="line-clamp-1 max-w-xs overflow-hidden text-ellipsis" v-html="truncateHtmlPreserve(data.description, 20)"></div>
                    </template>

                    <template v-if="props.hasPermission" #editor="{ data, field }">
                        <Editor v-model="data[field]" editorStyle="height: 200px">
                            <template #toolbar>
                                <span class="ql-formats">
                                    <button class="ql-bold"></button>
                                    <button class="ql-italic"></button>
                                    <button class="ql-underline"></button>
                                </span>
                            </template>
                        </Editor>
                    </template>
                </Column>

                <Column field="status_id" header="Status">
                    <template #body="{ data }">
                        <Tag :value="data.status?.name" :severity="data.status?.severity" />
                    </template>
                    <template v-if="props.hasPermission" #editor="{ data }">
                        <Dropdown v-model="data.status_id" :options="props.statuses" optionLabel="name" optionValue="id" class="w-full" />
                    </template>
                </Column>

                <Column field="priority_id" header="Priority">
                    <template #body="{ data }">
                        <Tag :value="data.priority?.name" :severity="data.priority?.severity" />
                    </template>
                    <template v-if="props.hasPermission" #editor="{ data }">
                        <Dropdown v-model="data.priority_id" :options="props.priorities" optionLabel="name" optionValue="id" class="w-full" />
                    </template>
                </Column>

                <Column field="start_date" header="Start">
                    <template #body="{ data }">
                        {{ moment(data.start_date).format('YYYY-MM-DD') }}
                    </template>

                    <template v-if="props.hasPermission" #editor="{ data, field }">
                        <InputText v-model="data[field]" type="date" class="w-full" />
                    </template>
                </Column>

                <Column field="due_date" header="Due">
                    <template #body="{ data }">
                        {{ moment(data.due_date).format('YYYY-MM-DD') }}
                    </template>

                    <template v-if="props.hasPermission" #editor="{ data, field }">
                        <InputText v-model="data[field]" type="date" class="w-full" />
                    </template>
                </Column>

                <Column field="progress" header="Progress">
                    <template #body="{ data }">
                        <ProgressBar :value="data.progress" :showValue="true" />
                    </template>
                </Column>

                <Column header="Action">
                    <template #body="{ data }">
                        <DropdownButton :items="items" :data="data" />
                    </template>
                </Column>

                <template #empty>
                    <p class="text-center">No Data Available</p>
                </template>
            </DataTable>
        </div>
    </div>

    <ProjectForm v-model:visible="visibleForm" :value="selected" :statuses="props.statuses" :priorities="props.priorities" />
    <ConfirmDialog />
    <Toast />
</template>