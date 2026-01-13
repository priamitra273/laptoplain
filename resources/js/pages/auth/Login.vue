<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import bgImage from '@/images/Bg.jpg';
import Logo from '@/images/logo-dark.png';
import { Head, useForm } from '@inertiajs/vue3';
import Button from 'primevue/button';
import Checkbox from 'primevue/checkbox';
import InputText from 'primevue/inputtext';

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
    <div class="relative min-h-screen overflow-hidden bg-surface-100">
        <div class="grid min-h-screen md:grid-cols-2 lg:grid-cols-5">
            <!-- LEFT IMAGE SECTION -->
            <div class="relative hidden overflow-hidden md:block lg:col-span-2">
                <!-- Background Image -->
                <img :src="bgImage" alt="Background" class="absolute inset-0 h-full w-full scale-105 bg-transparent object-cover object-center" />

                <!-- Overlay -->
                <div class="absolute inset-0 bg-gradient-to-br from-[#536976]/80 to-[#292E49]/90"></div>

                <!-- Content -->
                <div class="relative z-10 flex h-full flex-col items-center justify-center p-6 md:p-8 lg:p-10">
                    <img :src="Logo" alt="Logo" class="mb-8 h-20 w-auto md:mb-10 md:h-24 lg:mb-12 lg:h-32" />
                    <div class="text-center">
                        <h1 class="font-serif text-2xl leading-tight text-white md:text-2xl lg:text-3xl">Welcome Back</h1>
                        <p class="mt-2 max-w-sm px-4 text-sm text-white/80 md:px-0">
                            Securely access your dashboard and manage everything in one place.
                        </p>
                    </div>
                </div>
            </div>

            <!-- RIGHT FORM SECTION -->
            <div class="flex items-center justify-center p-6 md:p-8 lg:col-span-3 lg:p-12">
                <Head title="Log in" />

                <form class="w-full max-w-md" @submit.prevent="submit">
                    <!-- Heading -->
                    <div class="mb-6 text-left md:mb-8">
                        <h2 class="mb-1 font-serif text-2xl text-surface-700 md:text-3xl">Log in</h2>
                        <span class="text-sm text-surface-500 md:text-base"> Enter your email and password </span>
                    </div>

                    <div class="space-y-4 md:space-y-5">
                        <!-- Status -->
                        <div v-if="status" class="rounded-md bg-green-50 p-3">
                            <p class="text-center text-sm font-medium text-green-600">
                                {{ status }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div class="text-left">
                            <label for="email" class="mb-2 block text-sm font-medium text-surface-600"> Email address </label>
                            <InputText
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="email@example.com"
                                class="w-full"
                                required
                                autofocus
                                autocomplete="email"
                                :invalid="!!form.errors.email"
                            />
                            <InputError :message="form.errors.email" class="mt-1.5 text-sm text-red-600" />
                        </div>

                        <!-- Password -->
                        <div class="text-left">
                            <label for="password" class="mb-2 block text-sm font-medium text-surface-600"> Password </label>
                            <InputText
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="••••••••"
                                class="w-full"
                                required
                                autocomplete="current-password"
                                :invalid="!!form.errors.password"
                            />
                            <InputError :message="form.errors.password" class="mt-1.5 text-sm text-red-600" />
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center gap-2">
                                <Checkbox v-model="form.remember" inputId="remember" :binary="true" />
                                <label for="remember" class="text-sm text-surface-600 md:text-base"> Remember me </label>
                            </div>

                            <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm text-primary hover:underline">
                                Forgot password?
                            </TextLink>
                        </div>

                        <div class="pt-2">
                            <Button type="submit" label="Log in" class="w-full" :loading="form.processing" :disabled="form.processing" />
                        </div>

                        <div class="text-center text-sm text-surface-600">
                            Don't have an account?
                            <TextLink :href="route('register')" class="font-medium text-primary hover:underline"> Sign up </TextLink>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
