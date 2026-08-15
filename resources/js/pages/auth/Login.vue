<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AppLayout });

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <div class="flex min-h-screen items-center justify-center p-4">
        <Head title="Log in" />

        <UCard class="w-full max-w-sm">
            <template #header>
                <div class="flex items-center gap-2">
                    <UIcon name="i-lucide-shield-check" class="size-6 text-primary" />
                    <h1 class="text-lg font-semibold">Log in</h1>
                </div>
            </template>

            <UAlert v-if="status" color="success" variant="subtle" :description="status" class="mb-4" />

            <form class="space-y-4" @submit.prevent="submit">
                <UFormField label="Email address" :error="form.errors.email" required>
                    <UInput v-model="form.email" type="email" placeholder="email@example.com" autocomplete="email" autofocus class="w-full" />
                </UFormField>

                <UFormField label="Password" :error="form.errors.password" required>
                    <UInput v-model="form.password" type="password" placeholder="••••••••" autocomplete="current-password" class="w-full" />
                </UFormField>

                <div class="flex items-center justify-between">
                    <UCheckbox v-model="form.remember" label="Remember me" />

                    <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm text-primary hover:underline">
                        Forgot password?
                    </Link>
                </div>

                <UButton type="submit" block icon="i-lucide-log-in" :loading="form.processing"> Log in </UButton>
            </form>
        </UCard>
    </div>
</template>
