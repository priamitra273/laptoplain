<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import bgImage from '@/images/Bg.jpg';
import Logo from '@/images/logo-white.png';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

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
    <div class="relative h-screen overflow-hidden bg-surface-100">
        <div class="grid h-full sm:grid-cols-3 lg:grid-cols-2">
            <!-- LEFT IMAGE SECTION -->
            <div class="relative hidden overflow-hidden sm:block">
                <!-- Background Image -->
                <img :src="bgImage" alt="Background" class="absolute inset-0 h-full w-full scale-105 object-cover object-center" />

                <!-- Overlay -->
                <div class="absolute inset-0 bg-gradient-to-br from-[#536976]/80 to-[#292E49]/90"></div>

                <div class="relative z-10 flex h-full flex-col items-center justify-center p-10">
                    <img :src="Logo" alt="Logo" class="mb-12 h-32 w-auto" />
                    <div class="text-center">
                        <h1 class="font-serif text-3xl leading-tight text-white">Welcome Back</h1>
                        <p class="mt-2 max-w-sm text-sm text-white/80">Securely access your dashboard and manage everything in one place.</p>
                    </div>
                </div>
            </div>

            <!-- RIGHT FORM SECTION -->
            <div class="col-span-2 flex items-center justify-center lg:col-span-1">
                <Head title="Log in" />

                <form class="relative w-[29rem] px-8 sm:px-12" @submit.prevent="submit">
                    <!-- Heading -->
                    <div class="mb-8 text-left">
                        <h2 class="mb-1 font-serif text-3xl text-surface-700">Log in</h2>
                        <span class="text-surface-500"> Enter your email and password </span>
                    </div>

                    <div class="grid grid-cols-12 gap-4">
                        <!-- Status -->
                        <div v-if="status" class="col-span-12">
                            <div class="mb-4 text-center text-sm font-medium text-green-600">
                                {{ status }}
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="col-span-12 text-left">
                            <Label class="mb-1 text-surface-400"> Email address </Label>
                            <Input
                                id="email"
                                v-model="form.email"
                                type="email"
                                placeholder="email@example.com"
                                class="w-full"
                                required
                                autofocus
                                autocomplete="email"
                            />
                            <InputError :message="form.errors.email" class="mt-1 text-sm text-red-600" />
                        </div>

                        <!-- Password -->
                        <div class="col-span-12 text-left">
                            <Label class="mb-1 text-surface-400"> Password </Label>
                            <Input
                                id="password"
                                v-model="form.password"
                                type="password"
                                placeholder="••••••••"
                                class="w-full"
                                required
                                autocomplete="current-password"
                            />
                            <InputError :message="form.errors.password" class="mt-1 text-sm text-red-600" />
                        </div>

                        <!-- Remember & Forgot -->
                        <div class="col-span-12 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Checkbox id="remember" v-model:checked="form.remember" />
                                <Label for="remember" class="text-surface-500"> Remember me </Label>
                            </div>

                            <TextLink v-if="canResetPassword" :href="route('password.request')" class="text-sm text-muted-foreground">
                                Forgot password?
                            </TextLink>
                        </div>

                        <!-- Submit -->
                        <div class="col-span-12">
                            <Button type="submit" class="mt-4 w-full" :disabled="form.processing">
                                <LoaderCircle v-if="form.processing" class="mr-2 h-4 w-4 animate-spin" />
                                Log in
                            </Button>
                        </div>

                        <!-- Register -->
                        <div class="col-span-12 text-center text-sm text-muted-foreground">
                            Don’t have an account?
                            <TextLink :href="route('register')"> Sign up </TextLink>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
