<script setup>
import { Head } from '@inertiajs/vue3'
import Navbar from '@/components/Navbar.vue'

</script>

<template>
    <Head title="About" />

    <Header />
    <Navbar />

    <!-- Main Container -->
    <div class="min-h-screen bg-white px-4 pt-10 pb-12">
        <div class="max-w-5xl mx-auto">

            <!-- Card -->
            <div class="bg-white rounded-2xl border border-gray-200 p-8 md:p-10">

                <!-- Title -->
                <div class="mb-6">
                    <h1 class="text-3xl font-bold text-gray-800">
                        About Connect-Shelf
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                      Repository and Management Portal of Academic Researches
                    </p>
                </div>

                <!-- Divider -->
                <div class="border-t border-gray-200 mb-6"></div>

                <!-- Description -->
                <div class="space-y-5 text-gray-600 leading-8 text-justify">
                    <p>
                        Connect-Shelf is a centralized research management system designed to streamline
                        the entire academic workflow — from submission to final manuscript archiving.
                        It enables students, advisers, and department heads to collaborate efficiently
                        within a structured and transparent digital environment.
                    </p>

                    <p>
                        The platform provides a unified space for managing research projects, tracking
                        manuscript progress, and handling approval workflows. With role-based access,
                        users can securely interact with the system according to their responsibilities,
                        ensuring accountability and organization throughout the research lifecycle.
                    </p>

                    <p>
                        By digitizing traditional processes, Connect-Shelf improves accessibility,
                        reduces manual workload, and ensures that academic outputs are properly stored,
                        searchable, and preserved for future reference.
                    </p>
                </div>

            </div>

        </div>
    </div>
</template>