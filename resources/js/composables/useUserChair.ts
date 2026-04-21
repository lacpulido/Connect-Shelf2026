import type { AdminUsersProps, User } from '@/types'
import { router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

export function useUserChair(props: AdminUsersProps) {
    const department = ref(props.filters.department ?? '')
    const userType = ref(props.filters.user_type ?? '')

    watch([department, userType], () => {
        router.get(
            route('admin.users'),
            {
                department: department.value || undefined,
                user_type: userType.value || undefined,
            },
            { preserveState: true, replace: true },
        )
    })

    const feedbackMessage = ref('')
    const showDepartmentSelect = ref<number | null>(null)
    const selectedDepartment = ref('')
    const processingIds = ref(new Set<number>())

    const isChair = (user: User) =>
        user.roles?.some((r) => r.id === props.chairRoleId) ?? false

    const departmentHasChair = (departmentId?: number | null) =>
        !!departmentId && props.departmentsWithChair.includes(departmentId)

    const isDepartmentTakenByName = (departmentName: string) =>
        props.users.some((u) => u.department?.name === departmentName && isChair(u))

    const toggleChair = async (user: User) => {
        if (processingIds.value.has(user.id)) return

        if (isChair(user)) {
            await router.post(
                route('admin.users.toggle-chair', { user: user.id }),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => {
                        router.reload({ only: ['users', 'departmentsWithChair'] })
                        feedbackMessage.value = 'Chair role removed.'
                    },
                },
            )
            return
        }

        showDepartmentSelect.value = user.id
    }

    const confirmAssignChair = async (userId: number) => {
        if (!selectedDepartment.value) return

        processingIds.value.add(userId)

        await router.post(
            route('admin.users.toggle-chair', { user: userId }),
            { department: selectedDepartment.value },
            {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload({ only: ['users', 'departmentsWithChair'] })
                    feedbackMessage.value = `Assigned as Department Chair of ${selectedDepartment.value}.`
                    showDepartmentSelect.value = null
                    selectedDepartment.value = ''
                },
            },
        )

        processingIds.value.delete(userId)
    }

    return {
        department,
        userType,
        feedbackMessage,
        showDepartmentSelect,
        selectedDepartment,
        isChair,
        departmentHasChair,
        isDepartmentTakenByName,
        toggleChair,
        confirmAssignChair,
    }
}