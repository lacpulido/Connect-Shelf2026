<script setup lang="ts">
import { Button } from '@/components/ui/button'
</script>


<template>
<div class="flex min-h-screen bg-muted/40">
<!-- Sidebar -->
<aside class="w-64 bg-background border-r p-4">
<h2 class="text-xl font-bold mb-6">Admin</h2>
<nav class="space-y-2">
<a href="/admin/dashboard" class="block text-sm font-medium">Dashboard</a>
<a href="#" class="block text-sm">Users</a>
<a href="#" class="block text-sm">Settings</a>
</nav>
</aside>


<!-- Main -->
<main class="flex-1 p-6">
<slot />
</main>
</div>
</template>