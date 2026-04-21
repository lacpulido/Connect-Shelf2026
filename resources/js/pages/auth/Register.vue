<script setup lang="ts">
import Navbar from '@/components/Navbar.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Eye, EyeOff, LoaderCircle, Lock, Mail } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    colleges: Array<any>;
    departments: Array<any>;
}>();

const step = ref(1);

const form = useForm({
    first_name: '',
    middle_name: '',
    last_name: '',
    extension_name: '',
    college_id: null as number | null,
    department_id: null as number | null,
    user_type: null as number | null,
    expertise: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const isValidName = (name: string) => {
    const trimmed = name.trim();

    const regex = /^[A-Za-z\s'-]+$/;

    return trimmed.length > 0 && regex.test(trimmed);
};

const filteredDepartments = computed(() => {
    if (!form.college_id) return [];
    return props.departments.filter((d) => d.college_id === form.college_id);
});

watch(
    () => form.college_id,
    () => {
        form.department_id = null;
    },
);

watch(
    () => form.user_type,
    (newValue) => {
        if (newValue !== 1) {
            form.expertise = '';
        }
    },
);
const validateStep = () => {
    form.clearErrors();

    if (step.value === 1) {
        if (!form.first_name) {
            form.setError('first_name', 'First name is required');
        } else if (!isValidName(form.first_name)) {
            form.setError('first_name', 'Invalid first name format');
        }
        if (form.middle_name && !isValidName(form.middle_name)) {
            form.setError('middle_name', 'Invalid middle name format');
        }
        if (!form.last_name) {
            form.setError('last_name', 'Last name is required');
        } else if (!isValidName(form.last_name)) {
            form.setError('last_name', 'Invalid last name format');
        }
    }

    if (step.value === 2) {
        if (!form.college_id) form.setError('college_id', 'College is required');
        if (!form.department_id) form.setError('department_id', 'Department is required');
        if (!form.user_type) form.setError('user_type', 'Affiliation is required');

        if (form.user_type === 1 && !form.expertise) {
            form.setError('expertise', 'Expertise is required');
        }
    }

    if (step.value === 3) {
        if (!form.email) form.setError('email', 'Email is required');

        if (!form.password) form.setError('password', 'Password is required');
        else if (form.password.length < 8) {
            form.setError('password', 'Must be at least 8 characters');
        }

        if (!form.password_confirmation) {
            form.setError('password_confirmation', 'Confirm your password');
        } else if (form.password !== form.password_confirmation) {
            form.setError('password_confirmation', 'Passwords do not match');
        }
    }

    return Object.keys(form.errors).length === 0;
};
const nextStep = () => {
    if (!validateStep()) return;
    if (step.value < 3) step.value++;
};

const prevStep = () => {
    if (step.value > 1) step.value--;
};

const submit = () => {
    if (!validateStep()) return;

    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};

const showPassword = ref(false);
const showConfirmPassword = ref(false);
</script>

<template>
    <Head title="Register" />
    <Navbar />

    <div class="flex min-h-screen items-center justify-center bg-white px-6">
        <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-2xl">
            <div class="mb-8 text-center">
                <h1 class="text-3xl font-bold text-[#0C4B05]">Create an Account</h1>
            </div>

            <form @submit.prevent="submit" class="space-y-5">
                <div v-if="step === 1" class="space-y-4">
                    <div>
                        <label class="text-black-700 mb-1 block text-sm"> First Name <span class="text-red-500">*</span> </label>
                        <input
                            v-model="form.first_name"
                            type="text"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-black focus:ring-0"
                        />
                        <p v-if="form.errors.first_name" class="mt-1 text-sm text-red-500">
                            {{ form.errors.first_name }}
                        </p>
                    </div>

                    <div>
                        <label class="text-black-700 mb-1 block text-sm"> Middle Name </label>
                        <input
                            v-model="form.middle_name"
                            type="text"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-black focus:ring-0"
                        />
                    </div>

                    <div>
                        <label class="text-black-700 mb-1 block text-sm"> Last Name <span class="text-red-500">*</span> </label>
                        <input
                            v-model="form.last_name"
                            type="text"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-black focus:ring-0"
                        />
                        <p v-if="form.errors.last_name" class="mt-1 text-sm text-red-500">
                            {{ form.errors.last_name }}
                        </p>
                    </div>

                    <div>
                        <label class="text-black-700 mb-1 block text-sm"> Extension Name </label>
                        <input
                            v-model="form.extension_name"
                            type="text"
                            placeholder="Jr., Sr., III"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-black focus:ring-0"
                        />
                    </div>
                </div>
                <div v-if="step === 2" class="space-y-4">
                    <div>
                        <label class="text-black-700 mb-1 block text-sm"> College <span class="text-red-500">*</span> </label>
                        <select
                            v-model="form.college_id"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-black focus:ring-0"
                        >
                            <option value="" disabled>Select College</option>
                            <option v-for="college in colleges" :key="college.id" :value="college.id">
                                {{ college.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.college_id" class="mt-1 text-sm text-red-500">
                            {{ form.errors.college_id }}
                        </p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-gray-700"> Department <span class="text-red-500">*</span> </label>
                        <select
                            v-model="form.department_id"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-black focus:ring-0"
                        >
                            <option value="" disabled>Select Department</option>
                            <option v-for="dept in filteredDepartments" :key="dept.id" :value="dept.id">
                                {{ dept.name }}
                            </option>
                        </select>
                        <p v-if="form.errors.department_id" class="mt-1 text-sm text-red-500">
                            {{ form.errors.department_id }}
                        </p>
                    </div>
                    <div>
                        <label class="text-black-700 mb-1 block text-sm"> Affiliation <span class="text-red-500">*</span> </label>
                        <select
                            v-model="form.user_type"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-black focus:ring-0"
                        >
                            <option value="" disabled>Select Affiliation</option>
                            <option :value="1">Faculty</option>
                            <option :value="2">Student</option>
                        </select>
                        <p v-if="form.errors.user_type" class="mt-1 text-sm text-red-500">
                            {{ form.errors.user_type }}
                        </p>
                    </div>
                    <div v-if="form.user_type === 1">
                        <label class="text-black-700 mb-1 block text-sm"> Expertise <span class="text-red-500">*</span> </label>
                        <textarea
                            v-model="form.expertise"
                            rows="3"
                            class="w-full rounded-xl border border-gray-300 px-4 py-3 outline-none transition focus:border-black focus:ring-0"
                        />
                        <p v-if="form.errors.expertise" class="mt-1 text-sm text-red-500">
                            {{ form.errors.expertise }}
                        </p>
                    </div>
                </div>
                <div v-if="step === 3" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm text-gray-700"> Email <span class="text-red-500">*</span> </label>
                        <div class="relative">
                            <Mail class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                            <input
                                type="email"
                                v-model="form.email"
                                class="w-full rounded-xl border border-gray-300 py-3 pl-12 pr-4 outline-none transition focus:border-black focus:ring-0"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-500">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm text-gray-700"> Password <span class="text-red-500">*</span> </label>

                        <div class="relative">
                            <Lock class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />

                            <input
                                :type="showPassword ? 'text' : 'password'"
                                v-model="form.password"
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

                        <p class="mt-2 text-xs text-gray-500">
                            Must be at least 8 characters and include uppercase, lowercase, number, and special character.
                        </p>

                        <p v-if="form.errors.password" class="mt-1 text-sm text-red-500">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm text-gray-700"> Confirm Password <span class="text-red-500">*</span> </label>
                        <div class="relative">
                            <Lock class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400" />
                            <input
                                :type="showConfirmPassword ? 'text' : 'password'"
                                v-model="form.password_confirmation"
                                class="w-full rounded-xl border border-gray-300 py-3 pl-12 pr-12 outline-none transition focus:border-black focus:ring-0"
                            />
                            <button
                                type="button"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-[#0C4B05]"
                                @click="showConfirmPassword = !showConfirmPassword"
                            >
                                <EyeOff v-if="showConfirmPassword" class="h-5 w-5" />
                                <Eye v-else class="h-5 w-5" />
                            </button>
                        </div>
                        <p v-if="form.errors.password_confirmation" class="mt-1 text-sm text-red-500">
                            {{ form.errors.password_confirmation }}
                        </p>
                    </div>
                </div>
                <!-- Step Indicator -->
                <div class="mt-6 text-left">
                    <p class="text-sm font-medium text-black">Step {{ step }} of 3</p>
                </div>
                <div class="mt-4 flex justify-between gap-3">
                    <button
                        v-if="step > 1"
                        type="button"
                        class="flex-1 rounded-full border border-gray-300 px-6 py-3 text-gray-700 hover:border-black"
                        @click="prevStep"
                    >
                        Back
                    </button>

                    <button
                        v-if="step < 3"
                        type="button"
                        class="flex-1 rounded-full bg-[#0C4B05] px-6 py-3 text-white hover:bg-[#0A3A03]"
                        @click="nextStep"
                    >
                        Next
                    </button>

                    <button
                        v-if="step === 3"
                        type="submit"
                        class="flex flex-1 items-center justify-center gap-2 rounded-full bg-[#FFCD00] px-6 py-3 text-[#0C4B05] transition-all duration-200 hover:bg-[#e6b800] hover:shadow-md"
                        :disabled="form.processing"
                    >
                        <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                        Register
                    </button>
                </div>

                <p class="text-medium text-black-600 mt-6 text-center">
                    Already have an account?
                    <a :href="route('login')" class="font-semibold text-[#0C4B05] underline hover:text-black"> Login </a>
                </p>
            </form>
        </div>
    </div>
</template>
