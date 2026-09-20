<script setup lang="ts"> 
import AuthLayout from '@/layouts/AuthLayout.vue';
import AuthSplitLayout from '@/layouts/auth/SplitLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineOptions({ layout: AuthLayout });

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <Head title="Forgot password" />

    <AuthSplitLayout title="Reset Your Password" description="Enter your email and we'll send you instructions to reset your password.">
        <form @submit.prevent="submit">
            <div class="mb-8 space-y-1.5">
                <h2 class="text-2xl font-semibold text-highlighted">Forgot password</h2>
                <p class="text-sm text-muted">Enter your email to receive reset instructions.</p>
            </div>

            <UAlert v-if="status" color="success" variant="subtle" :description="status" class="mb-5" />

            <div class="flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                    <Label value="Email address" required />
                    <UInput
                        v-model="form.email"
                        type="email"
                        placeholder="email@example.com"
                        autocomplete="email"
                        autofocus
                        size="lg"
                        :highlight="!!form.errors.email"
                    />
                    <InputError v-if="form.errors.email" :message="form.errors.email" />
                </div>

                <UButton
                    type="submit"
                    label="Send reset link"
                    size="lg"
                    block
                    class="mt-1"
                    :loading="form.processing"
                    :disabled="form.processing"
                />

                <p class="text-center text-sm text-muted">
                    Or, return to 
                    <ULink :to="route('login')" class="font-medium text-primary hover:underline">log in</ULink>
                </p>
            </div>
        </form>
    </AuthSplitLayout>
</template>
