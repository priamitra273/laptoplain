<script setup lang="ts">
import FilterResetButton from '@/components/FilterResetButton.vue';
import PriorityBadgeSelect from '@/components/PriorityBadgeSelect.vue';
import SeverityBadgeSelect from '@/components/SeverityBadgeSelect.vue';
import StatusFilterPills, { type StatusPillOption } from '@/components/StatusFilterPills.vue';
import { computed, ref } from 'vue';
import type { MyTaskBadge, MyTaskProject } from './types';

defineProps<{
    projects: MyTaskProject[];
    statuses: StatusPillOption[];
    priorities: MyTaskBadge[];
    types: MyTaskBadge[];
    total: number | null;
    disabled?: boolean;
}>();

const search = defineModel<string>('search', { required: true });
const projectId = defineModel<string | undefined>('projectId', { required: true });
const statusId = defineModel<string | null>('statusId', { required: true });
const priorityId = defineModel<string | undefined>('priorityId', { required: true });
const typeId = defineModel<string | undefined>('typeId', { required: true });
const view = defineModel<'board' | 'list'>('view', { required: true });

const emit = defineEmits<{ clear: [] }>();

const expanded = ref(false);

/** Tombol "Filters" tetap menyala saat dilipat kalau ada filter aktif di dalamnya. */
const hasAdvancedFilters = computed(() => !!(projectId.value || priorityId.value || typeId.value));
const hasAnyFilter = computed(() => !!(search.value.trim() || statusId.value) || hasAdvancedFilters.value);
</script>

<template>
    <div class="flex flex-col gap-2 py-2.5">
        <div class="flex flex-wrap items-center gap-2">
            <UInput v-model="search" icon="i-lucide-search" placeholder="Search assignments..." :disabled="disabled" class="w-full sm:w-72" />

            <div class="ms-auto flex flex-wrap items-center gap-2">
                <UButton
                    icon="i-lucide-filter"
                    :label="expanded ? 'Hide' : 'Filters'"
                    :variant="expanded || hasAdvancedFilters ? 'solid' : 'outline'"
                    color="neutral"
                    class="rounded-full"
                    :aria-expanded="expanded"
                    :disabled="disabled"
                    @click="expanded = !expanded"
                />

                <FilterResetButton v-if="hasAnyFilter" :disabled="disabled" @click="emit('clear')" />

                <UFieldGroup>
                    <UButton
                        label="Board"
                        icon="i-lucide-columns-3"
                        :color="view === 'board' ? 'primary' : 'neutral'"
                        :variant="view === 'board' ? 'solid' : 'subtle'"
                        :disabled="disabled"
                        @click="view = 'board'"
                    />
                    <UButton
                        label="List"
                        icon="i-lucide-list"
                        :color="view === 'list' ? 'primary' : 'neutral'"
                        :variant="view === 'list' ? 'solid' : 'subtle'"
                        :disabled="disabled"
                        @click="view = 'list'"
                    />
                </UFieldGroup>
            </div>
        </div>

        <StatusFilterPills v-model="statusId" :options="statuses" :total="total" :disabled="disabled" />

        <div v-if="expanded" class="border-default flex flex-wrap items-center gap-4 border-t pt-3">
            <USelectMenu
                v-model="projectId"
                :items="projects"
                value-key="id"
                label-key="title"
                icon="i-lucide-folder"
                placeholder="Project"
                color="neutral"
                variant="outline"
                :disabled="disabled"
                class="w-44 rounded-full"
            />

            <PriorityBadgeSelect v-model="priorityId" :items="priorities" placeholder="Priority" :disabled="disabled" class="w-44 rounded-full" />

            <SeverityBadgeSelect v-model="typeId" :items="types" placeholder="Type" :disabled="disabled" class="w-44 rounded-full" />

        </div>
    </div>
</template>
