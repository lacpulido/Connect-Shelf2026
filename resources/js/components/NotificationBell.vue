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

watch(
    () => page.props.notifications,
    (newNotifications) => {
        const mappedNotifications: NotificationItem[] = (newNotifications || []).map((n: any) => ({
            id: n.id,
            title: n.title || 'Notification',
            message: n.message || 'New notification',
            created_at: n.created_at || '',
            status: n.status || 'UNREAD',
        }))

        notifications.value = mappedNotifications

        if (isFirstLoad.value) {
            mappedNotifications.forEach((notif) => {
                toastedIds.value.add(notif.id)
            })
            isFirstLoad.value = false
            return
        }

        mappedNotifications.forEach((notif) => {
            const isUnread = notif.status === 'UNREAD'
            const alreadyToasted = toastedIds.value.has(notif.id)

            if (isUnread && !alreadyToasted) {
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

    open.value = false
}

const showToast = (title: string, message: string) => {
    const containerId = 'notification-toast-container'
    let container = document.getElementById(containerId)

    if (!container) {
        container = document.createElement('div')
        container.id = containerId
        container.className =
            'fixed top-5 right-5 z-[9999] flex w-full max-w-sm flex-col gap-3 px-3 sm:px-0'
        document.body.appendChild(container)
    }

    const toast = document.createElement('div')
    toast.className =
        'pointer-events-auto overflow-hidden rounded-2xl border border-[#d9e7d6] bg-white shadow-2xl ring-1 ring-black/5 transition-all duration-300'

    toast.innerHTML = `
        <div class="p-4">
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#edf5ec]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#0C4B05]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V4a2 2 0 10-4 0v1.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0a3 3 0 11-6 0m6 0H9"/>
                    </svg>
                </div>

                <div class="min-w-0 flex-1">
                    <p class="break-words text-sm font-semibold text-[#0C4B05]">${title}</p>
                    <p class="mt-1 break-words text-sm leading-5 text-gray-600">${message}</p>
                </div>

                <button
                    type="button"
                    class="ml-2 flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-400 transition hover:bg-[#f3f8f2] hover:text-[#0C4B05]"
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
    if (diff < 3600) {
        const minutes = Math.floor(diff / 60)
        return `${minutes} min ago`
    }
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
            class="relative flex h-11 w-11 items-center justify-center rounded-full border border-[#d9e7d6] bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:bg-[#f6faf5] hover:shadow-md"
        >
            <Bell class="h-5 w-5 text-[#0C4B05]" stroke-width="2" />

            <span
                v-if="unreadCount > 0"
                class="absolute -right-1 -top-1 flex min-h-[20px] min-w-[20px] items-center justify-center rounded-full border-2 border-white bg-[#0C4B05] px-1.5 text-[10px] font-bold leading-none text-white shadow"
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
                class="absolute right-0 z-50 mt-3 w-[380px] overflow-hidden rounded-2xl border border-[#d9e7d6] bg-white shadow-2xl ring-1 ring-black/5"
            >
                <div class="border-b border-[#e8f0e6] bg-[#f7fbf6] px-5 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-[#edf5ec]">
                                <BellRing class="h-5 w-5 text-[#0C4B05]" />
                            </div>

                            <div>
                                <h3 class="text-sm font-semibold text-[#0C4B05]">Notifications</h3>
                                <p class="text-xs text-gray-500">
                                    {{ unreadCount }} unread notification{{ unreadCount === 1 ? '' : 's' }}
                                </p>
                            </div>
                        </div>

                        <button
                            v-if="unreadCount > 0"
                            type="button"
                            @click="markAllAsRead"
                            class="inline-flex items-center gap-1 rounded-full bg-[#edf5ec] px-3 py-1.5 text-xs font-semibold text-[#0C4B05] transition hover:bg-[#e2efdf]"
                        >
                            <CheckCheck class="h-3.5 w-3.5" />
                            Mark all
                        </button>
                    </div>
                </div>

                <div v-if="notifications.length === 0" class="px-6 py-10">
                    <div class="flex flex-col items-center justify-center text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-[#edf5ec]">
                            <Bell class="h-6 w-6 text-[#0C4B05]" />
                        </div>
                        <h4 class="mt-4 text-sm font-semibold text-[#0C4B05]">No notifications yet</h4>
                        <p class="mt-1 max-w-[240px] text-xs leading-5 text-gray-500">
                            New alerts and updates will appear here once available.
                        </p>
                    </div>
                </div>

                <div v-else class="max-h-[420px] overflow-y-auto bg-[#fbfdfb] p-2">
                    <div
                        v-for="n in recentNotifications"
                        :key="n.id"
                        @click="openNotification(n)"
                        class="mb-2 cursor-pointer rounded-xl border p-4 transition duration-200 last:mb-0 hover:-translate-y-0.5 hover:shadow-sm"
                        :class="
                            n.status === 'UNREAD'
                                ? 'border-[#d9e7d6] bg-[#f2f8f1] hover:bg-[#edf5ec]'
                                : 'border-[#ebf1ea] bg-white hover:bg-[#f8fbf7]'
                        "
                    >
                        <div class="flex items-start gap-3">
                            <div
                                class="mt-0.5 h-2.5 w-2.5 shrink-0 rounded-full"
                                :class="n.status === 'UNREAD' ? 'bg-[#0C4B05]' : 'bg-gray-300'"
                            ></div>

                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-3">
                                    <p class="truncate text-sm font-semibold text-[#0C4B05]">
                                        {{ n.title }}
                                    </p>

                                    <span
                                        class="shrink-0 rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide"
                                        :class="
                                            n.status === 'UNREAD'
                                                ? 'bg-[#e6f1e4] text-[#0C4B05]'
                                                : 'bg-gray-100 text-gray-500'
                                        "
                                    >
                                        {{ n.status }}
                                    </span>
                                </div>

                                <p class="mt-1 line-clamp-2 text-sm leading-5 text-gray-600">
                                    {{ n.message }}
                                </p>

                                <div class="mt-3 flex items-center justify-between gap-3">
                                    <p class="text-xs font-medium text-[#0C4B05]">
                                        {{ formatRelativeTime(n.created_at) }}
                                    </p>
                                    <p class="text-[11px] text-gray-400">
                                        {{ formatTime(n.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition>
    </div>
</template>