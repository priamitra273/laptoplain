<script lang="ts" setup>
type Color = 'primary' | 'secondary' | 'success' | 'info' | 'warning' | 'error' | 'neutral'

interface ConfirmDialogProps {
    title?: string
    description?: string
    color?: Color
}

withDefaults(defineProps<ConfirmDialogProps>(), {
    title: 'Are you sure?',
    description: 'This action cannot be undone.',
    color: 'error'
})

const emits = defineEmits<{
    close: [value: boolean]
}>()
</script>

<template>
    <UModal :title="title" :description="description" :dismissible="false" :ui="{ footer: 'justify-end' }">
        <template #footer>
            <UButton label="Cancel" color="neutral" variant="outline" @click="emits('close', false)" />
            <UButton label="Confirm" :color="color" @click="emits('close', true)" />
        </template>
    </UModal>
</template>
