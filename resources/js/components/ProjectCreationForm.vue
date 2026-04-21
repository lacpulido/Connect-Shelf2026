<script setup lang="ts">
import { ref } from 'vue';

const emit = defineEmits<{
  (e: 'created'): void;
}>();

const title = ref('');
const description = ref('');
const error = ref('');

const submitForm = () => {
  if (!title.value.trim()) {
    error.value = 'Project title is required';
    return;
  }
  emit('created');
};
</script>
<template>
  <div class="project-creation-form max-w-md mx-auto mt-10 p-6 border rounded shadow">
    <h2 class="text-xl font-semibold mb-4">Create Your Project</h2>

    <form @submit.prevent="submitForm" class="space-y-4">
      <div>
        <label for="title" class="block font-medium mb-1">Project Title</label>
        <input
          id="title"
          v-model="title"
          type="text"
          placeholder="Enter project title"
          class="w-full border px-3 py-2 rounded"
        />
      </div>

      <div>
        <label for="description" class="block font-medium mb-1">Project Description</label>
        <textarea
          id="description"
          v-model="description"
          placeholder="Enter project description"
          rows="4"
          class="w-full border px-3 py-2 rounded"
        ></textarea>
      </div>
      <div v-if="error" class="text-red-600 text-sm">{{ error }}</div>
      <button
        type="submit"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
      >
        Create Project
      </button>
    </form>
  </div>
</template>
