<script lang="ts" setup>
const props = withDefaults(
    defineProps<{
        title?: string;
        description?: string;
    }>(),
    {
        title: "Team Members",
        description: "Manage your team members here",
    }
);
</script>

<template>
    <div class="mb-4">
        <div class="flex flex-col flex-wrap justify-between gap-5 md:flex-row md:items-center">
            <div class="flex items-center gap-5">
                <div class="flex flex-col gap-1 *:leading-none">
                    <slot name="title">
                        <p v-if="props.title" class="font-semibold" v-html="props.title" />
                    </slot>
                    <slot name="description">
                        <p v-if="props.description" class="text-muted-foreground text-sm" v-html="props.description" />
                    </slot>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 md:flex md:flex-wrap md:items-center">
                <slot>
                </slot>
            </div>
        </div>
    </div>
</template>
