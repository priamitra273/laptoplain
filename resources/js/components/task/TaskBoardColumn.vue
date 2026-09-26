<script setup lang="ts">
import FieldLabel from '@/components/ui/FieldLabel.vue';
import { severityBoxStyle, severityDotClass } from '@/lib/utils';
import type { BoardBadge } from './types';

defineProps<{
    status: BoardBadge;
    /** Jumlah sebenarnya di server, bukan jumlah kartu yang sudah dimuat. */
    count: number;
    collapsed: boolean;
    isDragOver: boolean;
    isDropTarget: boolean;
    /** Kolom tidak bisa menyimpulkan sendiri apakah slot kartunya kosong, jadi dikirim dari luar. */
    isEmpty: boolean;
}>();

const emit = defineEmits<{ toggle: [] }>();
</script>

<template>
    <section
        class="flex shrink-0 flex-col gap-3 rounded-xl p-3 ring ring-default transition-shadow"
        :class="[collapsed ? 'w-12' : 'w-72', isDragOver ? 'ring-2 ring-primary' : '']"
        :style="severityBoxStyle(status.severity)"
        :aria-label="status.name"
    >
        <button
            v-if="collapsed"
            type="button"
            class="flex flex-col items-center gap-2 py-1"
            :aria-label="`Expand ${status.name}`"
            @click="emit('toggle')"
        >
            <UIcon name="i-lucide-chevron-right" class="size-3.5 shrink-0 text-muted" />

            <span class="size-1.5 shrink-0 rounded-full" :class="severityDotClass(status.severity)" />

            <span class="text-xs text-dimmed tabular-nums">{{ count }}</span>

            <!-- `rotate-180` membalik arah baca teks vertikal jadi bawah-ke-atas, bukan atas-ke-bawah. -->
            <FieldLabel :title="status.name" class="rotate-180 [writing-mode:vertical-rl]" />
        </button>

        <template v-else>
            <div class="flex items-center gap-2">
                <UButton
                    icon="i-lucide-chevron-left"
                    color="neutral"
                    variant="ghost"
                    size="xs"
                    square
                    :aria-label="`Collapse ${status.name}`"
                    @click="emit('toggle')"
                />

                <span class="size-1.5 shrink-0 rounded-full" :class="severityDotClass(status.severity)" />

                <FieldLabel :title="status.name" class="min-w-0 flex-1 truncate" />
                <span class="text-xs text-dimmed tabular-nums">{{ count }}</span>

                <slot name="header-actions" />
            </div>

            <!-- Di luar area scroll: isi di sini tetap terlihat walau daftar kartunya panjang. -->
            <slot name="lead" />

            <!-- Hanya daftar kartu yang menggulir, supaya isi footer kolom tetap terlihat. -->
            <div class="flex max-h-[calc(100vh-320px)] flex-col gap-2 overflow-y-auto">
                <slot />

                <div
                    v-if="isEmpty"
                    class="flex flex-col items-center justify-center gap-1.5 rounded-lg border border-dashed py-7 text-center text-xs"
                    :class="isDragOver ? 'border-primary text-primary' : 'border-default text-muted'"
                >
                    <UIcon :name="isDropTarget ? 'i-lucide-corner-left-down' : 'i-lucide-inbox'" class="size-5" />
                    {{ isDropTarget ? 'Drop here' : 'No tasks' }}
                </div>
            </div>

            <slot name="footer" />
        </template>
    </section>
</template>
