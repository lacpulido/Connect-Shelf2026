// src/composables/useUsers.ts
import type { User } from '@/types';
import axios from 'axios';
import { computed, ref } from 'vue';

export function useUsers() {
    const users = ref<User[]>([]);
    const searchQuery = ref('');
    const userTypeFilter = ref('All');
    const departmentFilter = ref('All');
    const userTypeOptions = ref<string[]>([]);
    const departmentOptions = ref<string[]>([]);
    const loading = ref(false);
    const error = ref('');
    const assigningChair = ref<number | null>(null);

    const userTypes = computed(() => ['All', ...userTypeOptions.value]);
    const departments = computed(() => ['All', ...departmentOptions.value]);

    const filteredUsers = computed(() => {
        return users.value.filter((user) => {
            const matchesSearch =
                searchQuery.value === '' ||
                user.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
                user.email.toLowerCase().includes(searchQuery.value.toLowerCase());

            const matchesType = userTypeFilter.value === 'All' || user.user_type.toString() === userTypeFilter.value;

            const matchesDept = departmentFilter.value === 'All' || user.department === departmentFilter.value;

            return matchesSearch && matchesType && matchesDept;
        });
    });

    const totalUsers = computed(() => users.value.length);
    const filteredCount = computed(() => filteredUsers.value.length);

    async function fetchUsers() {
        loading.value = true;
        error.value = '';

        try {
            const { data } = await axios.get('/admin/users/data');
            users.value = data.map((u: any) => ({
                id: u.id,
                name: `${u.first_name} ${u.last_name}`,
                email: u.email,
                user_type: Number(u.user_type),
                department: u.department,
                department_id: u.department_id,
                is_dept_chair: Boolean(u.is_dept_chair),
            }));
        } catch (err) {
            console.error('Fetch error:', err);
            error.value = 'Failed to load users';
        } finally {
            loading.value = false;
        }
    }

    async function fetchFilters() {
        const { data } = await axios.get('/admin/users/filters');
        userTypeOptions.value = data.user_types;
        departmentOptions.value = data.departments;
    }

    async function toggleDepartmentChair(user: User) {
        if (assigningChair.value === user.id) return;

        if (!user.department_id) {
            alert('This user does not have a department assigned');
            return;
        }

        const action = user.is_dept_chair ? 'remove' : 'assign';
        const confirmMsg = user.is_dept_chair
            ? `Remove ${user.name} as Department Chairperson of ${user.department}?`
            : `Assign ${user.name} as Department Chairperson of ${user.department}?\n\nNote: This will remove the previous chairperson if one exists.`;

        if (!confirm(confirmMsg)) return;

        assigningChair.value = user.id;

        try {
            console.log('Sending request:', {
                user_id: user.id,
                department_id: user.department_id,
                action: action,
            });

            const { data } = await axios.post('/admin/users/assign-dept-chair', {
                user_id: user.id,
                department_id: user.department_id,
                action: action,
            });

            await fetchUsers();
            alert(data.message);
        } catch (err: any) {
            const errorMsg = err.response?.data?.message || 'Failed to update department chairperson';
            alert(errorMsg);
            console.error('Error details:', err.response?.data);
        } finally {
            assigningChair.value = null;
        }
    }

    function resetFilters() {
        searchQuery.value = '';
        userTypeFilter.value = 'All';
        departmentFilter.value = 'All';
    }

    return {
        users,
        searchQuery,
        userTypeFilter,
        departmentFilter,
        userTypeOptions,
        departmentOptions,
        userTypes,
        departments,
        filteredUsers,
        totalUsers,
        filteredCount,
        loading,
        error,
        assigningChair,
        fetchUsers,
        fetchFilters,
        toggleDepartmentChair,
        resetFilters,
    };
}
