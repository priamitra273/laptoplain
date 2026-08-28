<script setup lang="ts">
import { getInitials, severityColor } from '@/lib/utils';
import { computed, ref } from 'vue';
import type { WorkloadStatusOption, WorkloadUserOption } from './types';
import { toneOf } from './workload';

const props = defineProps<{
    userOptions: WorkloadUserOption[];
    statusOptions: WorkloadStatusOption[];
}>();

const emit = defineEmits<{
    clear: [];
}>();

const search = defineModel<string>('search', { required: true });
const names = defineModel<string[]>('names', { required: true });
const statuses = defineModel<number[]>('statuses', { required: true });

const activeChips = computed(() => [
    ...props.statusOptions
        .filter((option) => statuses.value.includes(option.id))
        .map((option) => ({
            key: `status-${option.id}`,
            label: option.name,
            tone: toneOf(option.severity),
            remove: () => (statuses.value = statuses.value.filter((id) => id !== option.id)),
        })),
    ...props.userOptions
        .filter((option) => names.value.includes(option.id))
        .map((option) => ({
            key: `user-${option.id}`,
            label: option.name,
            tone: toneOf(null),
            remove: () => (names.value = names.value.filter((id) => id !== option.id)),
        })),
    ...(search.value ? [{ key: 'search', label: `Name: “${search.value}”`, tone: toneOf(null), remove: () => (search.value = '') }] : []),
]);

// Panel dibuka sendiri kalau halaman dimuat dengan filter aktif, supaya kontrolnya
// tidak tersembunyi di balik tombol saat hasilnya sudah tersaring.
const expanded = ref(activeChips.value.length > 0);
</script>

<template>
    <div class="flex flex-col gap-2 border-y border-default bg-elevated/30 px-4 py-2.5">
        <div class="flex flex-wrap items-center gap-2">
            <UInput v-if="expanded" v-model="search" icon="i-lucide-search" placeholder="Search name" class="w-full sm:w-60" />

            <USelectMenu
                v-if="expanded"
                v-model="names"
                :items="userOptions"
                label-key="name"
                value-key="id"
                multiple
                placeholder="Users"
                color="neutral"
                variant="outline"
                class="w-full sm:w-44"
            >
                <template #item-leading="{ item }">
                    <UAvatar :src="item.avatar_url ?? undefined" :alt="item.name" :text="getInitials(item.name)" size="2xs" />
                </template>
            </USelectMenu>

            <USelectMenu
                v-if="expanded"
                v-model="statuses"
                :items="statusOptions"
                label-key="name"
                value-key="id"
                multiple
                placeholder="Status"
                color="neutral"
                variant="outline"
                class="w-full sm:w-44"
            >
                <template #item-label="{ item }">
                    <UBadge :color="severityColor(item.severity)" variant="subtle" size="sm">{{ item.name }}</UBadge>
                </template>
            </USelectMenu>

            <UButton
                icon="i-lucide-filter"
                :label="expanded ? 'Hide' : 'Filters'"
                color="neutral"
                variant="ghost"
                size="xs"
                class="ms-auto"
                :aria-expanded="expanded"
                @click="expanded = !expanded"
            >
                <template v-if="activeChips.length" #trailing>
                    <UBadge color="primary" variant="subtle" size="sm" class="tabular-nums">{{ activeChips.length }}</UBadge>
                </template>
            </UButton>
        </div>

        <div v-if="activeChips.length" class="flex flex-wrap items-center gap-1.5">
            <span class="me-0.5 text-xs tracking-wide text-muted uppercase">Active</span>

            <button
                v-for="chip in activeChips"
                :key="chip.key"
                type="button"
                class="flex items-center gap-1.5 rounded-md px-2 py-1 text-xs ring"
                :class="[chip.tone.soft, chip.tone.ring, chip.tone.text]"
                @click="chip.remove()"
            >
                <span class="max-w-40 truncate">{{ chip.label }}</span>
                <UIcon name="i-lucide-x" class="size-3 shrink-0" />
            </button>

            <UButton label="Clear all filters" variant="link" color="neutral" size="xs" class="-my-1" @click="emit('clear')" />
        </div>
    </div>
</template>
