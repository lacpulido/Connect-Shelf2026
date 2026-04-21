<script setup lang="ts">
import { Users, FolderKanban, LoaderCircle, CheckCircle } from 'lucide-vue-next'

defineProps<{
  totalUsers: number
  totalProjects: number
  ongoingProjects: number
  completedProjects: number
}>()
</script>

<template>
  <div class="grid gap-4 md:grid-cols-4">

    <!-- Total Users -->
    <div class="rounded-2xl border bg-white p-6 shadow transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-xl">
      <div class="w-fit rounded-xl bg-blue-100 p-3 text-blue-600">
        <Users class="h-6 w-6" />
      </div>
      <p class="mt-4 text-sm text-gray-500">Total Users</p>
      <h2 class="mt-2 text-3xl font-bold">{{ totalUsers }}</h2>
    </div>

   

    <!-- Ongoing -->
    <div class="rounded-2xl border bg-white p-6 shadow transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-xl">
      <div class="w-fit rounded-xl bg-orange-100 p-3 text-orange-600">
        <LoaderCircle class="h-6 w-6" />
      </div>
      <p class="mt-4 text-sm text-gray-500">Ongoing Projects</p>
      <h2 class="mt-2 text-3xl font-bold">{{ ongoingProjects }}</h2>
    </div>
 
    <!-- Completed -->
    <div class="rounded-2xl border bg-white p-6 shadow transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-xl">
      <div class="w-fit rounded-xl bg-green-100 p-3 text-green-600">
        <CheckCircle class="h-6 w-6" />
      </div>
      <p class="mt-4 text-sm text-gray-500">Completed Projects</p>
      <h2 class="mt-2 text-3xl font-bold">{{ completedProjects }}</h2>
    </div>
     <!-- Total Projects -->
    <div class="rounded-2xl border bg-white p-6 shadow transition-all duration-300 ease-in-out hover:-translate-y-1 hover:shadow-xl">
      <div class="w-fit rounded-xl bg-purple-100 p-3 text-purple-600">
        <FolderKanban class="h-6 w-6" />
      </div>
      <p class="mt-4 text-sm text-gray-500">Total Projects</p>
      <h2 class="mt-2 text-3xl font-bold">{{ totalProjects }}</h2>
    </div>
  </div>
</template>