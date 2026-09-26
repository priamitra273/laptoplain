<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const emits = defineEmits<{ close: [boolean] }>();

const form = useForm({
    password: '',
});

const submit = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => emits('close', true),
        onError: () => form.reset('password'),
    });
};
</script>

<template>
    <UModal title="Are you sure you want to delete your account?" :dismissible="false" :ui="{ footer: 'justify-end' }">
        <template #body>
            <form class="flex flex-col gap-3" @submit.prevent="submit">
                <p class="text-sm text-muted">
                    Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password to
                    confirm you would like to permanently delete your account.
                </p>

                <UFormField label="Password" name="password" :error="form.errors.password">
                    <UInput v-model="form.password" type="password" placeholder="Password" autofocus class="w-full" />
                </UFormField>
            </form>
        </template>

        <template #footer>
            <UButton label="Cancel" color="neutral" variant="outline" @click="emits('close', false)" />
            <UButton label="Delete account" color="error" :loading="form.processing" :disabled="form.processing" @click="submit" />
        </template>
    </UModal>
</template>
