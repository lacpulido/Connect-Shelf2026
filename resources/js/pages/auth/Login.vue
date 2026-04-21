<script setup lang="ts">
import Navbar from '@/components/Navbar.vue'
import { Head, useForm } from '@inertiajs/vue3'
import { Eye, EyeOff, Lock, Mail } from 'lucide-vue-next'
import { ref } from 'vue'

const props = defineProps<{
    status?: string
    canResetPassword: boolean
    download?: string | null
}>()

const showPassword = ref(false)

const form = useForm({
    email: '',
    password: '',
    remember: false,
    download: props.download ?? '',
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head title="Log in" />
    <Navbar />

    <div class="flex min-h-screen items-center justify-center bg-white px-6">
        <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-2xl">
            <div class="mb-6 text-center">
                <h1 class="text-3xl font-bold text-[#0C4B05]">Welcome Back</h1>
                <p class="text-sm text-gray-600">Log in to your account</p>
            </div>

            <div
                v-if="status"
                class="mb-4 text-center text-sm font-medium text-green-600"
            >
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <input v-model="form.download" type="hidden" />

                <div>
                    <label class="mb-1 block text-sm text-gray-700">Email</label>

                    <div class="relative">
                        <Mail
                            class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                        />

                        <input
                            v-model="form.email"
                            type="email"
                            required
                            placeholder="email@example.com"
                            class="w-full rounded-xl border border-gray-300 py-3 pl-12 pr-4 outline-none transition focus:border-black focus:ring-0"
                        />
                    </div>

                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">
                        {{ form.errors.email }}
                    </p>
                </div>

                <div>
                    <label class="mb-1 block text-sm text-gray-700">Password</label>

                    <div class="relative">
                        <Lock
                            class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                        />

                        <input
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            placeholder="••••••••"
                            class="w-full rounded-xl border border-gray-300 py-3 pl-12 pr-12 outline-none transition focus:border-black focus:ring-0"
                        />

                        <button
                            type="button"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#0C4B05]"
                            @click="showPassword = !showPassword"
                        >
                            <EyeOff v-if="showPassword" class="h-5 w-5" />
                            <Eye v-else class="h-5 w-5" />
                        </button>
                    </div>

                    <p v-if="form.errors.password" class="mt-1 text-sm text-red-500">
                        {{ form.errors.password }}
                    </p>
                </div>

                <div class="flex items-center justify-between text-sm">
                    <label class="flex items-center gap-2">
                        <input
                            v-model="form.remember"
                            type="checkbox"
                            class="accent-black"
                        />
                        Remember me
                    </label>

                    <a
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-black underline transition hover:text-[#0C4B05]"
                    >
                        Forgot Password?
                    </a>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="mt-4 flex w-full items-center justify-center gap-2 rounded-xl bg-[#FFCD00] py-3 text-[#0C4B05] transition hover:scale-105 hover:bg-[#e6b800] hover:shadow-lg disabled:opacity-70"
                >
                    Log in
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-black">
                Don't have an account?

                <a
                    :href="route('register')"
                    class="font-semibold text-[#0C4B05] underline transition hover:text-black"
                >
                    Sign up
                </a>
            </p>
        </div>
    </div>
</template>