<script setup lang="ts">
import { buildNavigationItems, toNavigationGroups } from '@/lib/menu';
import type { Menu, SidebarMenuItem } from '@/types';
import { computed } from 'vue';

interface Props {
    data?: Menu[];
}

const props = withDefaults(defineProps<Props>(), {
    data: () => [],
});

const resolveHref = (routeName: string): string => {
    try {
        return route(routeName, undefined, false) as string;
    } catch {
        return '#';
    }
};

const bySequence = (a: Menu, b: Menu) => a.sequence_number - b.sequence_number;

const tree = computed<SidebarMenuItem[]>(() => {
    const active = props.data.filter((menu) => menu.is_active);
    const roots = [...active.filter((menu) => !menu.parent_uuid)].sort(bySequence);

    return roots.map((root) => {
        const children = [...active.filter((menu) => menu.parent_uuid === root.uuid)].sort(bySequence);

        return {
            label: root.label,
            icon: root.icon,
            to: root.route,
            items: children.length
                ? children.map((child) => ({
                      label: child.label,
                      icon: child.icon,
                      to: child.route,
                      items: null,
                  }))
                : null,
        };
    });
});

const groups = computed(() => toNavigationGroups(buildNavigationItems(tree.value, resolveHref)));
</script>

<template>
    <UCard :ui="{ root: 'bg-sidebar', body: 'p-3 sm:p-3' }">
        <p class="px-2 pb-2 text-xs font-medium">Sidebar preview</p>

        <div v-if="groups.length" class="pointer-events-none select-none" aria-hidden="true">
            <UNavigationMenu :items="groups" orientation="vertical" :ui="{ childList: 'min-w-44', separator: 'h-px' }" />
        </div>

        <p v-else class="px-2 py-6 text-center text-sm text-muted">No active menu to preview.</p>

        <p class="mt-3 px-2 text-xs leading-snug text-muted">
            Active menus only. The real sidebar also hides items the signed-in role has no permission for.
        </p>
    </UCard>
</template>
