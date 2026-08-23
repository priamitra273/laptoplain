<script setup lang="ts">
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import type { AuthFormField, FormError, FormSubmitEvent } from '@nuxt/ui';
import { ref, useTemplateRef } from 'vue';

defineOptions({ layout: AuthLayout });

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const authForm = useTemplateRef('authForm');
const loading = ref(false);

// `name` wajib di tiap field; type 'password' otomatis dapat tombol lihat/sembunyi.
const fields: AuthFormField[] = [
    {
        name: 'email',
        type: 'email',
        label: 'Email',
        placeholder: 'nama@contoh.com',
        autocomplete: 'email',
        autofocus: true,
        required: true,
    },
    {
        name: 'password',
        type: 'password',
        label: 'Kata sandi',
        placeholder: '••••••••',
        autocomplete: 'current-password',
        required: true,
    },
    { name: 'remember', type: 'checkbox', label: 'Ingat saya', defaultValue: false },
];

// Validasi klien seadanya; kebenaran kredensial tetap diputuskan server.
const validate = (state: Record<string, unknown>): FormError[] => {
    const errors: FormError[] = [];

    if (!String(state.email ?? '')) {
        errors.push({ name: 'email', message: 'Email wajib diisi.' });
    }

    if (!String(state.password ?? '')) {
        errors.push({ name: 'password', message: 'Kata sandi wajib diisi.' });
    }

    return errors;
};

interface LoginPayload extends Record<string, string | boolean> {
    email: string;
    password: string;
    remember: boolean;
}

const onSubmit = (event: FormSubmitEvent<LoginPayload>) => {
    router.post(route('login'), event.data, {
        onStart: () => (loading.value = true),
        onFinish: () => (loading.value = false),
        onError: (errors) => authForm.value?.formRef?.setErrors(Object.entries(errors).map(([name, message]) => ({ name, message }))),
    });
};
</script>

<template>
    <div class="flex min-h-screen items-center justify-center p-4">

        <Head title="Log in" />

        <div class="w-full max-w-sm">
            <UAlert v-if="props.status" color="success" variant="subtle" :description="props.status" class="mb-4" />

            <UAuthForm ref="authForm" icon="i-lucide-shield-check" title="Selamat datang kembali"
                description="Masuk untuk melanjutkan ke dashboard." :fields="fields" :validate="validate"
                :loading="loading" :submit="{ label: 'Masuk' }" @submit="onSubmit">
                <template v-if="props.canResetPassword" #password-hint>
                    <ULink tabindex="-1" :to="route('password.request')" class="font-medium text-primary">
                        Lupa sandi?
                    </ULink>
                </template>
            </UAuthForm>
        </div>
    </div>
</template>
