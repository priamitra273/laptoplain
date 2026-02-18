<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { PrimeSeverity, Project } from '@/types';
import { router } from '@inertiajs/vue3';
import { FilterMatchMode, FilterOperator } from '@primevue/core/api';
import 'emoji-mart-vue-fast/css/emoji-mart.css';
import emojiData from 'emoji-mart-vue-fast/data/all.json';
// @ts-ignore
import { EmojiIndex, Picker } from 'emoji-mart-vue-fast/src';
import moment from 'moment';
import { MenuItem } from 'primevue/menuitem';
import ProgressBar from 'primevue/progressbar';
import Tag from 'primevue/tag';
import { useConfirm } from 'primevue/useconfirm';
import { useToast } from 'primevue/usetoast';
import { computed, ref, watch } from 'vue';
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
    status_id: { value: null, matchMode: FilterMatchMode.IN },
    priority_id: { value: null, matchMode: FilterMatchMode.IN },
    start_date: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.DATE_IS }] },
    due_date: { operator: FilterOperator.AND, constraints: [{ value: null, matchMode: FilterMatchMode.DATE_IS }] },
    progress: { value: [0, 100], matchMode: FilterMatchMode.BETWEEN },
});

const deleteLoading = ref(false);
const visibleForm = ref<boolean>(false);
const selected = ref<Project | undefined>(undefined);
const showEmojiPicker = ref<{ [key: string]: boolean }>({});

const projects = computed(() => {
    return props.projects.map((project) => {
        return {
            ...project,
            start_date: moment(project.start_date).toDate(),
            due_date: moment(project.due_date).toDate(),
        };
    });
});

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
        },
    },
];

if (props.hasPermission) {
    items.push({
        label: 'Delete',
        command(event) {
            confirmDelete(event.item.data);
        },
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
    deleteLoading.value = true;
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
                onFinish: () => (deleteLoading.value = false),
            });
        },
        reject: () => (deleteLoading.value = false),
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

    showEmojiPicker.value[data.id] = false;
};

const toggleEmojiPicker = (dataId: string) => {
    showEmojiPicker.value[dataId] = !showEmojiPicker.value[dataId];
};

const currentPage = ref(0);
const rowsPerPage = ref(10);

const onPage = (event: any) => {
    currentPage.value = event.page;
    rowsPerPage.value = event.rows;
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

            <Button :disabled="!props.hasPermission" icon="pi pi-plus" label="Add Project" @click="goToCreate" />
        </div>

        <div class="card overflow-hidden">
            <DataTable
                :value="projects"
                v-model:filters="filters"
                data-key="id"
                editMode="cell"
                filter-display="menu"
                paginator
                :rows="10"
                :rowsPerPageOptions="[10, 25, 50]"
                :globalFilterFields="['project_no', 'title', 'description']"
                striped-rows
                row-hover
                removable-sort
                :closeOnEscape="false"
                @page="onPage"
                @cell-edit-complete="onCellEditComplete"
                scrollable
                scrollHeight="flex"
            >
                <Column header="No" class="w-12 text-center">
                    <template #body="{ index }">
                        {{ currentPage * rowsPerPage + index + 1 }}
                    </template>
                </Column>

                <Column field="project_no" header="Project No" sortable class="w-32">
                    <template #body="{ data }">
                        <span class="font-mono text-sm">
                            {{ data.project_no ?? '-' }}
                        </span>
                    </template>
                </Column>

                <!-- Kolom Title dengan Emoji -->
                <Column field="title" header="Title" sortable :sortOrder="-1">
                    <template #body="{ data }">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">{{ data.emoji || '😀' }}</span>
                            <span>{{ data.title }}</span>
                        </div>
                    </template>

                    <template v-if="props.hasPermission" #editor="{ data, field }">
                        <div class="flex w-full items-center gap-2">
                            <div class="relative">
                                <button type="button" @click.stop="toggleEmojiPicker(data.id)" class="rounded px-2 py-1 text-2xl hover:bg-gray-100">
                                    {{ data.emoji || '😀' }}
                                </button>
                                <div v-if="showEmojiPicker[data.id]" @click.stop class="absolute left-0 top-full z-50 mt-1">
                                    <Picker
                                        :data="emojiIndex"
                                        @select="(emoji: any) => onEmojiSelect(emoji, data)"
                                        set="native"
                                        :native="true"
                                        title="Pick an emoji"
                                        emoji="point_up"
                                    />
                                </div>
                            </div>
                            <InputText v-model="data[field]" class="flex-1" />
                        </div>
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

                <Column field="status_id" header="Status" sortable filter-field="status_id" :show-filter-match-modes="false" style="width: 4rem">
                    <template #body="{ data }">
                        <Tag :value="data.status?.name" :severity="data.status?.severity" />
                    </template>

                    <template #filter="{ filterModel }">
                        <MultiSelect v-model="filterModel.value" :options="props.statuses" option-label="name" option-value="id" placeholder="Any">
                            <template #option="{ option }">
                                <Tag :value="option.name" :severity="option.severity" />
                            </template>
                        </MultiSelect>
                    </template>

                    <template v-if="props.hasPermission" #editor="{ data }">
                        <Dropdown v-model="data.status_id" :options="props.statuses" optionLabel="name" optionValue="id" class="w-full" />
                    </template>
                </Column>

                <Column
                    field="priority_id"
                    header="Priority"
                    sortable
                    filter-field="priority_id"
                    :show-filter-match-modes="false"
                    style="width: 4rem"
                >
                    <template #body="{ data }">
                        <Tag :value="data.priority?.name" :severity="data.priority?.severity" />
                    </template>

                    <template #filter="{ filterModel }">
                        <MultiSelect v-model="filterModel.value" :options="props.priorities" option-label="name" option-value="id" placeholder="Any">
                            <template #option="{ option }">
                                <Tag :value="option.name" :severity="option.severity" />
                            </template>
                        </MultiSelect>
                    </template>

                    <template v-if="props.hasPermission" #editor="{ data }">
                        <Dropdown v-model="data.priority_id" :options="props.priorities" optionLabel="name" optionValue="id" class="w-full" />
                    </template>
                </Column>

                <Column field="start_date" header="Start" sortable filter-field="start_date" data-type="date">
                    <template #body="{ data }">
                        {{ moment(data.start_date).format('YYYY-MM-DD') }}
                    </template>

                    <template #filter="{ filterModel }">
                        <DatePicker v-model="filterModel.value" dateFormat="yy-mm-dd" placeholder="yyyy-mm-dd" />
                    </template>

                    <template v-if="props.hasPermission" #editor="{ data, field }">
                        <InputText v-model="data[field]" type="date" class="w-full" />
                    </template>
                </Column>

                <Column field="due_date" header="Due" sortable filter-field="due_date" data-type="date">
                    <template #body="{ data }">
                        {{ moment(data.due_date).format('YYYY-MM-DD') }}
                    </template>

                    <template #filter="{ filterModel }">
                        <DatePicker v-model="filterModel.value" dateFormat="yy-mm-dd" placeholder="yyyy-mm-dd" />
                    </template>

                    <template v-if="props.hasPermission" #editor="{ data, field }">
                        <InputText v-model="data[field]" type="date" class="w-full" />
                    </template>
                </Column>

                <Column field="progress" header="Progress" sortable :show-filter-match-modes="false">
                    <template #body="{ data }">
                        <ProgressBar :value="data.progress" :showValue="true" />
                    </template>

                    <template #filter="{ filterModel }">
                        <Slider v-model="filterModel.value" range class="m-4"></Slider>
                        <div class="flex items-center justify-between px-2">
                            <span>{{ filterModel.value ? filterModel.value[0] : 0 }}</span>
                            <span>{{ filterModel.value ? filterModel.value[1] : 100 }}</span>
                        </div>
                    </template>
                </Column>

                <Column header="Action" frozen alignFrozen="right" style="min-width: 100px">
                    <template #body="{ data }">
                        <div class="flex gap-2">
                            <Button
                                icon="pi pi-eye"
                                severity="secondary"
                                size="small"
                                :disabled="deleteLoading"
                                @click="router.visit(route('project.show', { encoded: data.id }))"
                                v-tooltip.bottom="'View Details'"
                            />
                            <Button
                                icon="pi pi-trash"
                                severity="danger"
                                size="small"
                                :disabled="deleteLoading || !hasPermission"
                                @click="confirmDelete(data)"
                                v-tooltip.bottom="'Delete'"
                            />
                        </div>
                    </template>
                </Column>

                <template #empty>
                    <p class="text-center">No Data Available</p>
                </template>
            </DataTable>
        </div>
    </div>

    <ProjectForm v-model:visible="visibleForm" :value="selected" :statuses="props.statuses" :priorities="props.priorities" />
</template>
