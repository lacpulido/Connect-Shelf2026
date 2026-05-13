<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount, onBeforeMount, watch } from 'vue'
import { usePage, router, usePoll } from '@inertiajs/vue3'
import { Bell, CheckCheck, BellRing } from 'lucide-vue-next'

interface NotificationItem {
    id: number | string
    title: string
    message: string
    created_at: string
    status: string
    type?: string
    reference_id?: number | string | null
    reference_type?: string | null
    modal_title?: string
    modal_message?: string
    action_label?: string | null
    action_url?: string | null
}

declare global {
    interface Window {
        __notificationBellMounted?: boolean
    }
}

const page = usePage<any>()
const user = computed(() => page.props.auth?.user ?? null)

const notifications = ref<NotificationItem[]>([])
const open = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)
const shouldRender = ref(true)

const toastedIds = ref<Set<number | string>>(new Set())
const isFirstLoad = ref(true)

const showNotificationModal = ref(false)
const selectedNotification = ref<NotificationItem | null>(null)

watch(
    () => page.props.notifications,
    (newNotifications) => {
        const mappedNotifications: NotificationItem[] = (newNotifications || []).map((n: any) => ({
            id: n.id,
            title: n.title || 'Notification',
            message: n.message || 'New notification',
            created_at: n.created_at || '',
            status: n.status || 'UNREAD',
            type: n.type || '',
            reference_id: n.reference_id ?? null,
            reference_type: n.reference_type ?? null,
            modal_title: n.modal_title || n.title || 'Notification',
            modal_message: n.modal_message || n.message || 'New notification',
            action_label: n.action_label || null,
            action_url: n.action_url || null,
        }))

        notifications.value = mappedNotifications

        if (isFirstLoad.value) {
            mappedNotifications.forEach((notif) => toastedIds.value.add(notif.id))
            isFirstLoad.value = false
            return
        }

        mappedNotifications.forEach((notif) => {
            if (notif.status === 'UNREAD' && !toastedIds.value.has(notif.id)) {
                showToast(notif.title, notif.message)
                toastedIds.value.add(notif.id)
            }
        })
    },
    { immediate: true, deep: true }
)

const unreadCount = computed(() =>
    notifications.value.filter((n) => n.status === 'UNREAD').length
)

const recentNotifications = computed(() => notifications.value.slice(0, 10))

onBeforeMount(() => {
    if (typeof window !== 'undefined') {
        if (window.__notificationBellMounted) {
            shouldRender.value = false
        } else {
            window.__notificationBellMounted = true
            shouldRender.value = true
        }
    }
})

const toggleBell = () => {
    open.value = !open.value
}

const markAsRead = (id: number | string) => {
    router.post(
        `/notifications/${id}/read`,
        {},
        {
            preserveScroll: true,
            preserveState: true,
            only: ['notifications', 'unread_notifications_count'],
            onSuccess: () => {
                const notif = notifications.value.find((n) => n.id === id)
                if (notif) notif.status = 'READ'
            },
        }
    )
}

const markAllAsRead = () => {
    router.post(
        '/notifications/read-all',
        {},
        {
            preserveScroll: true,
            preserveState: true,
            only: ['notifications', 'unread_notifications_count'],
            onSuccess: () => {
                notifications.value.forEach((n) => {
                    n.status = 'READ'
                })
            },
        }
    )
}

const openNotification = (notif: NotificationItem) => {
    if (notif.status === 'UNREAD') {
        markAsRead(notif.id)
    }

    selectedNotification.value = notif
    showNotificationModal.value = true
    open.value = false
}

const closeModal = () => {
    showNotificationModal.value = false
    selectedNotification.value = null
}

const showToast = (title: string, message: string) => {
    const containerId = 'notification-toast-container'
    let container = document.getElementById(containerId)

    if (!container) {
        container = document.createElement('div')
        container.id = containerId
        container.className =
            'fixed top-4 right-4 z-[9999] flex w-full max-w-[calc(100vw-1rem)] sm:max-w-sm flex-col gap-3 px-2 sm:px-0'
        document.body.appendChild(container)
    }

    const toast = document.createElement('div')
    toast.className =
        'pointer-events-auto overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl ring-1 ring-black/5 transition-all duration-300'

    toast.innerHTML = `
        <div class="bg-white p-4">
            <div class="flex items-start gap-3">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/>
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="break-words text-sm font-semibold text-gray-900">${title}</p>
                    <p class="mt-1 break-words text-sm leading-5 text-gray-600">${message}</p>
                </div>

                <button
                    type="button"
                    class="ml-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-white text-gray-400 transition hover:bg-white hover:text-gray-700"
                    aria-label="Close notification"
                >
                    ✕
                </button>
            </div>
        </div>
    `

    container.prepend(toast)

    const closeButton = toast.querySelector('button')

    const removeToast = () => {
        toast.classList.add('opacity-0', 'translate-x-4')
        setTimeout(() => {
            toast.remove()
            if (container && container.childElementCount === 0) {
                container.remove()
            }
        }, 300)
    }

    closeButton?.addEventListener('click', removeToast)
    setTimeout(removeToast, 3000)
}

const formatTime = (date: string) => {
    if (!date) return ''

    const d = new Date(date)
    if (isNaN(d.getTime())) return ''

    return d.toLocaleString('en-PH', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
        hour12: true,
    })
}

const formatRelativeTime = (date: string) => {
    if (!date) return ''

    const d = new Date(date)
    if (isNaN(d.getTime())) return ''

    const diff = Math.floor((Date.now() - d.getTime()) / 1000)

    if (diff < 60) return 'Just now'
    if (diff < 3600) return `${Math.floor(diff / 60)} min ago`
    if (diff < 86400) {
        const hours = Math.floor(diff / 3600)
        return `${hours} hr${hours > 1 ? 's' : ''} ago`
    }
    if (diff < 604800) {
        const days = Math.floor(diff / 86400)
        return `${days} day${days > 1 ? 's' : ''} ago`
    }

    return formatTime(date)
}

const closeDropdown = (event: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
        open.value = false
    }
}

onMounted(() => {
    if (!shouldRender.value) return
    document.addEventListener('click', closeDropdown)
})

onBeforeUnmount(() => {
    if (shouldRender.value && typeof window !== 'undefined') {
        window.__notificationBellMounted = false
    }

    document.removeEventListener('click', closeDropdown)
})

usePoll(5000, {
    only: ['notifications', 'unread_notifications_count'],
})
</script>

<template>
    <div v-if="user && shouldRender" ref="dropdownRef" class="relative">
        <button
            type="button"
            @click.stop="toggleBell"
            class="relative flex h-10 w-10 sm:h-11 sm:w-11 items-center justify-center rounded-full border border-gray-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-white hover:shadow-md"
        >
            <Bell class="h-5 w-5 text-gray-700" stroke-width="2" />

            <span
                v-if="unreadCount > 0"
                class="absolute -right-1 -top-1 flex min-h-[18px] min-w-[18px] sm:min-h-[20px] sm:min-w-[20px] items-center justify-center rounded-full border-2 border-white bg-gray-900 px-1 text-[10px] font-bold leading-none text-white shadow"
            >
                {{ unreadCount > 9 ? '9+' : unreadCount }}
            </span>
        </button>

        <transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 translate-y-2 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 translate-y-2 scale-95"
        >
            <div
                v-if="open"
                class="absolute right-0 z-50 mt-3 w-[300px] max-w-[calc(100vw-1rem)] sm:w-[320px] md:w-[340px] overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-2xl ring-1 ring-black/5"
            >
                <div class="border-b border-gray-200 bg-white px-4 py-3 sm:px-4 sm:py-3.5">
                    <div class="flex items-center justify-between gap-2">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-gray-200 bg-white">
                                <BellRing class="h-4.5 w-4.5 text-gray-700" />
                            </div>

                            <div class="min-w-0">
                                <h3 class="truncate text-sm font-semibold text-gray-900">Notifications</h3>
                                <p class="text-[11px] text-gray-500">
                                    {{ unreadCount }} unread notification{{ unreadCount === 1 ? '' : 's' }}
                                </p>
                            </div>
                        </div>

                        <button
                            v-if="unreadCount > 0"
                            type="button"
                            @click="markAllAsRead"
                            class="inline-flex shrink-0 items-center gap-1 rounded-full border border-gray-200 bg-white px-2.5 py-1.5 text-[11px] font-semibold text-gray-700 transition hover:bg-white"
                        >
                            <CheckCheck class="h-3.5 w-3.5" />
                            Mark all
                        </button>
                    </div>
                </div>

                <div v-if="notifications.length === 0" class="bg-white px-5 py-8">
                    <div class="flex flex-col items-center justify-center text-center">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full border border-gray-200 bg-white">
                            <Bell class="h-5 w-5 text-gray-700" />
                        </div>
                        <h4 class="mt-3 text-sm font-semibold text-gray-900">No notifications yet</h4>
                        <p class="mt-1 max-w-[220px] text-xs leading-5 text-gray-500">
                            New updates will appear here once available.
                        </p>
                    </div>
                </div>

                <div v-else class="max-h-[320px] sm:max-h-[360px] overflow-y-auto bg-white p-2">
                    <div
                        v-for="n in recentNotifications"
                        :key="n.id"
                        @click="openNotification(n)"
                        class="mb-2 cursor-pointer rounded-xl border border-gray-200 bg-white p-3 transition duration-200 last:mb-0 hover:-translate-y-0.5 hover:bg-white hover:shadow-sm"
                    >
                        <div class="flex items-start gap-2.5">
                            <div class="mt-1 flex h-4 w-4 shrink-0 items-center justify-center">
                                <span
                                    v-if="n.status === 'UNREAD'"
                                    class="h-2.5 w-2.5 rounded-full bg-gray-900"
                                ></span>
                            </div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p
                                        class="truncate pr-1 text-sm"
                                        :class="n.status === 'UNREAD' ? 'font-bold text-gray-900' : 'font-medium text-gray-600'"
                                    >
                                        {{ n.title }}
                                    </p>

                                    <span
                                        v-if="n.status === 'UNREAD'"
                                        class="mt-1 h-2 w-2 shrink-0 rounded-full bg-gray-900"
                                        title="Unread"
                                    ></span>
                                </div>

                                <p
                                    class="mt-1 line-clamp-2 text-xs sm:text-sm leading-5"
                                    :class="n.status === 'UNREAD' ? 'font-medium text-gray-800' : 'font-normal text-gray-500'"
                                >
                                    {{ n.message }}
                                </p>

                                <div class="mt-2 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                    <p
                                        class="text-[11px]"
                                        :class="n.status === 'UNREAD' ? 'font-semibold text-gray-700' : 'font-medium text-gray-500'"
                                    >
                                        {{ formatRelativeTime(n.created_at) }}
                                    </p>
                                    <p class="text-[10px] sm:text-[11px] text-gray-400">
                                        {{ formatTime(n.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>

        <div
            v-if="showNotificationModal && selectedNotification"
            class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4"
        >
            <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl">
                <div class="mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">
                        {{ selectedNotification.modal_title || selectedNotification.title }}
                    </h3>
                    <p class="mt-2 text-sm leading-6 text-gray-600">
                        {{ selectedNotification.modal_message || selectedNotification.message }}
                    </p>
                </div>

                <div class="flex justify-end gap-3">
                    <button
                        type="button"
                        @click="closeModal"
                        class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-white"
                    >
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>