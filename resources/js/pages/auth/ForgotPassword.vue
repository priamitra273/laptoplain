<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

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
    <div class="overflow-hidden margin-0 relative h-screen bg-surface-100">
        <div class="grid sm:grid-cols-3 lg:grid-cols-2 h-full">
            <div class="hidden sm:block bg-gradient-to-br from-[#536976] to-[#292E49] overflow-hidden">
                <img src="https://images.unsplash.com/photo-1488229297570-58520851e868?q=80&w=1469&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="" srcset="" class="w-full h-full object-cover" />
            </div>

            <div class="col-span-2 lg:col-span-1 flex items-center justify-center">
                <div>
                    <div class="w-full text-center">
                        <Head title="Forgot password" />
                        
                        <div class="px-12 md:p-0 w-[29rem] relative">
                            <div class="col-span-9 text-left mb-8">
                                <h2 class="mb-1 text-3xl font-serif text-surface-700 dark:text-surface-900">Forgot Password</h2>
                                <span class="text-surface-500 dark:text-surface-300">No worries, we'll send you instructions for reset!</span>
                            </div>

                            <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
                                {{ status }}
                            </div>

                            <form @submit.prevent="submit">
                                <div class="grid grid-cols-12 gap-8">
                                    <div class="col-span-12 text-left">
                                        <Label class="text-surface-400 dark:text-surface-400 mb-1">Email address</Label>
                                        <div class="mt-1">
                                            <Input 
                                                id="email" 
                                                v-model="form.email" 
                                                type="email" 
                                                name="email"
                                                placeholder="Enter your email address" 
                                                class="w-full" 
                                                autocomplete="off"
                                                autofocus
                                            />
                                            <InputError :message="form.errors.email" class="mt-1 inline-block text-red-600 text-sm" />
                                        </div>
                                    </div>

                                    <div class="col-span-12">
                                        <Button 
                                            type="submit"
                                            class="w-full"
                                            :disabled="form.processing"
                                            :loading="form.processing"
                                        >
                                            <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                                            Reset Password
                                        </Button>
                                        
                                        <div class="mt-4 text-center text-sm text-muted-foreground">
                                            <span>Or, return to </span>
                                            <TextLink :href="route('login')">log in</TextLink>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
