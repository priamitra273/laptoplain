<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import AuthSplitLayout from '@/layouts/auth/SplitLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineOptions({ layout: AuthLayout });

const props = defineProps<{
    email: string;
    token: string;
}>();

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Reset password" />

    <AuthSplitLayout title="Choose a New Password" description="Pick a password you have not used before to keep your account secure.">
        <form @submit.prevent="submit">
            <div class="mb-8 space-y-1.5">
                <h2 class="text-2xl font-semibold text-highlighted">Reset password</h2>
                <p class="text-sm text-muted">Enter a new password for your account.</p>
            </div>

            <div class="flex flex-col gap-5">
                <div class="flex flex-col gap-2">
                    <Label value="Email address" required />
                    <UInput v-model="form.email" type="email" autocomplete="email" size="lg" readonly :highlight="!!form.errors.email" />
                    <InputError v-if="form.errors.email" :message="form.errors.email" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label value="New password" required />
                    <UInput
                        v-model="form.password"
                        :type="showPassword ? 'text' : 'password'"
                        placeholder="••••••••"
                        autocomplete="new-password"
                        autofocus
                        size="lg"
                        :highlight="!!form.errors.password"
                    >
                        <template #trailing>
                            <UButton
                                :icon="showPassword ? 'i-lucide-eye-off' : 'i-lucide-eye'"
                                :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                color="neutral"
                                variant="link"
                                size="sm"
                                tabindex="-1"
                                @click="showPassword = !showPassword"
                            />
                        </template>
                    </UInput>
                    <InputError v-if="form.errors.password" :message="form.errors.password" />
                </div>

                <div class="flex flex-col gap-2">
                    <Label value="Confirm password" required />
                    <UInput
                        v-model="form.password_confirmation"
                        :type="showPassword ? 'text' : 'password'"
                        placeholder="••••••••"
                        autocomplete="new-password"
                        size="lg"
                        :highlight="!!form.errors.password_confirmation"
                    />
                    <InputError v-if="form.errors.password_confirmation" :message="form.errors.password_confirmation" />
                </div>

                <UButton type="submit" label="Reset password" size="lg" block class="mt-1" :loading="form.processing" :disabled="form.processing" />

                <p class="text-center text-sm text-muted">
                    Or, return to
                    <ULink :to="route('login')" class="font-medium text-primary hover:underline">log in</ULink>
                </p>
            </div>
        </form>
    </AuthSplitLayout>
</template>
