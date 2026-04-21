<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Bell } from 'lucide-vue-next'
import axios from 'axios'

const isOpen = ref(false)
const notifications = ref<any[]>([])
const unreadCount = ref(0)

const fetchNotifications = async () => {
  const response = await axios.get('/admin/notifications')
  notifications.value = response.data.notifications
  unreadCount.value = response.data.unread_count
}

const toggleDropdown = async () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    await fetchNotifications()
  }
}

const markAllAsRead = async () => {
  await axios.post('/admin/notifications/read-all')
  await fetchNotifications()
}

onMounted(fetchNotifications)
</script>

<template>
  <div class="relative">
    <!-- Bell Button -->
    <button
      @click="toggleDropdown"
      class="relative inline-flex h-10 w-10 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 shadow-sm hover:bg-gray-50"
    >
      <Bell class="h-5 w-5" />

      <!-- Badge -->
      <span
        v-if="unreadCount > 0"
        class="absolute -top-1 -right-1 rounded-full bg-red-500 px-1.5 text-xs text-white"
      >
        {{ unreadCount }}
      </span>
    </button>

    <!-- Dropdown -->
    <div
      v-if="isOpen"
      class="absolute right-0 z-50 mt-2 w-80 rounded-lg border bg-white shadow-lg"
    >
      <div class="flex items-center justify-between border-b px-4 py-2">
        <h3 class="font-semibold">Notifications</h3>
        <button
          v-if="notifications.length"
          @click="markAllAsRead"
          class="text-xs text-blue-600 hover:underline"
        >
          Mark all as read
        </button>
      </div>

      <div v-if="notifications.length">
        <div
          v-for="notif in notifications"
          :key="notif.id"
          class="border-b px-4 py-3 text-sm hover:bg-gray-50"
        >
          <p class="font-medium">
            {{ notif.data.title }}
          </p>
          <p class="text-gray-600">
            {{ notif.data.message }}
          </p>
          <p class="text-xs text-gray-400">
            {{ new Date(notif.created_at).toLocaleString() }}
          </p>
        </div>
      </div>

      <div v-else class="px-4 py-6 text-center text-sm text-gray-500">
        No notifications available.
      </div>
    </div>
  </div>
</template>